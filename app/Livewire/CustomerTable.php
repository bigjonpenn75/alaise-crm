<?php

namespace App\Livewire;

use App\Models\Business;
use App\Models\Client;
use App\Models\Lead;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Searchable, paged customer list: bookings for a studio, clients for the clinic. */
class CustomerTable extends Component
{
    use WithPagination;

    public Business $business;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $month = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'month'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $like = '%'.addcslashes(trim($this->search), '%_\\').'%';
        $search = fn ($q) => $q->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
            ->orWhere('tel', 'like', $like)->orWhere('email', 'like', $like);
        $month = preg_match('/^\d{4}-\d{2}$/', $this->month) ? $this->month : null;
        $dateColumn = $this->business->is_clinic ? 'created_date' : 'date_added';

        $query = $this->business->is_clinic
            ? Client::where('business_id', $this->business->id)->with(['staffMember', 'technician', 'location', 'service'])->withCount('appointments')
            : Lead::where('business_id', $this->business->id)->with(['staffMember', 'technician', 'location', 'source']);

        $rows = $query
            ->when(trim($this->search) !== '', fn ($q) => $q->where($search))
            ->when($month, fn ($q) => $q->whereBetween($dateColumn, [$month.'-01', date('Y-m-t', strtotime($month.'-01'))]))
            ->orderByDesc($dateColumn)->orderByDesc('id')
            ->paginate(50);

        return view('livewire.customer-table', ['rows' => $rows]);
    }
}
