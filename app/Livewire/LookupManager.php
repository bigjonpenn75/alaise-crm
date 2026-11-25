<?php

namespace App\Livewire;

use App\Enums\DispositionCategory;
use App\Models\Business;
use App\Models\Disposition;
use App\Models\LeadSource;
use App\Models\Location;
use App\Models\MarketingSource;
use App\Models\Service;
use App\Models\StaffMember;
use App\Models\Technician;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One settings list (staff names, locations, technicians, ...): add, rename,
 * activate/deactivate. Rows are never deleted because old records point at them.
 */
class LookupManager extends Component
{
    private const TYPES = [
        'staff' => [StaffMember::class, 'Staff (who took the booking)'],
        'locations' => [Location::class, 'Locations'],
        'technicians' => [Technician::class, 'Technicians'],
        'services' => [Service::class, 'Services'],
        'dispositions' => [Disposition::class, 'Appointment dispositions'],
        'marketing-sources' => [MarketingSource::class, 'Marketing sources'],
        'businesses' => [Business::class, 'Businesses'],
        'lead-sources' => [LeadSource::class, 'Lead sources'],
    ];

    #[Locked]
    public string $type;

    #[Locked]
    public ?int $businessId = null;

    public string $title = '';
    public string $newName = '';
    public ?int $editing = null;
    public string $editName = '';

    public function mount(string $type, ?Business $business = null, ?string $title = null): void
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        $this->type = $type;
        $this->businessId = $business?->id;
        $this->title = $title ?? self::TYPES[$type][1];
    }

    /** @return class-string<Model> */
    private function model(): string
    {
        return self::TYPES[$this->type][0];
    }

    private function scoped()
    {
        abort_unless(auth()->user()->canManage(), 403);
        if ($this->businessId) {
            abort_unless(auth()->user()->canAccessBusiness($this->businessId), 403);

            return $this->model()::where('business_id', $this->businessId);
        }

        return $this->model()::query();
    }

    public function add(): void
    {
        $this->validate(['newName' => ['required', 'string', 'max:100']], attributes: ['newName' => 'name']);
        $this->scoped(); // authorise
        $attributes = ['name' => trim($this->newName)];
        if ($this->businessId) {
            $attributes['business_id'] = $this->businessId;
        }
        if ($this->type === 'businesses') {
            $code = str($this->newName)->slug()->limit(20, '')->toString() ?: 'business';
            while (Business::where('code', $code)->exists()) {
                $code = substr($code, 0, 17).'-'.random_int(10, 99);
            }
            $attributes += ['code' => $code, 'short_name' => str($this->newName)->limit(30, '')->toString(), 'uses_reception_split' => true];
        }
        $this->model()::create($attributes);
        $this->reset('newName');
    }

    public function edit(int $id): void
    {
        $this->editing = $id;
        $this->editName = $this->scoped()->findOrFail($id)->name;
    }

    public function rename(): void
    {
        $this->validate(['editName' => ['required', 'string', 'max:100']], attributes: ['editName' => 'name']);
        $this->scoped()->findOrFail($this->editing)->update(['name' => trim($this->editName)]);
        $this->editing = null;
    }

    public function toggle(int $id, string $field = 'active'): void
    {
        $allowed = ['active', ...match ($this->type) {
            'staff' => ['is_reception'],
            'lead-sources' => ['counts_as_reception'],
            'businesses' => ['uses_reception_split'],
            default => [],
        }];
        abort_unless(in_array($field, $allowed, true), 404);
        $row = $this->scoped()->findOrFail($id);
        $row->update([$field => ! $row->{$field}]);
    }

    public function setCategory(int $id, string $category): void
    {
        abort_unless($this->type === 'dispositions' && DispositionCategory::tryFrom($category), 404);
        $this->scoped()->findOrFail($id)->update(['category' => $category]);
    }

    public function render()
    {
        return view('livewire.lookup-manager', [
            'rows' => $this->scoped()->orderByDesc('active')->orderBy('name')->get(),
            'flag' => match ($this->type) {
                'staff' => ['is_reception', 'Reception'],
                'lead-sources' => ['counts_as_reception', 'Counts as reception'],
                'businesses' => ['uses_reception_split', 'Reception split'],
                default => null,
            },
            'categories' => $this->type === 'dispositions' ? DispositionCategory::cases() : [],
        ]);
    }
}
