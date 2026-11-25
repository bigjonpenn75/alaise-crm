<?php

namespace App\Http\Controllers;

use App\Enums\DispositionCategory;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Client;
use App\Models\Disposition;
use App\Models\Note;
use App\Models\Technician;
use App\Services\ReportService;
use App\Support\DateRange;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class TechnicianController extends Controller
{
    public function performance(Request $request, ReportService $reports)
    {
        $business = $this->clinic($request);
        $month = DateRange::month($request);

        return view('technicians.performance', ['business' => $business, 'month' => $month] + $reports->technicianPerformance($business, $month));
    }

    /** The appointments behind one cell of the performance table. */
    public function appointments(Request $request, Technician $technician, ReportService $reports)
    {
        $this->authorize('access-business', $technician->business);
        $month = DateRange::month($request);
        $segment = in_array($request->query('segment'), ['consultations', 'repeat'], true) ? $request->query('segment') : 'all';
        $category = DispositionCategory::tryFrom((string) $request->query('category'));
        $disposition = $request->integer('disposition') ?: null;

        $query = Appointment::where('technician_id', $technician->id)
            ->between($month->startOfMonth()->toDateString(), $month->endOfMonth()->toDateString())
            ->with(['client', 'location', 'disposition']);
        if ($segment !== 'all') {
            $ids = $reports->consultationIds($technician->business, $month);
            $segment === 'consultations' ? $query->whereIn('id', $ids) : $query->whereNotIn('id', $ids);
        }
        $query->when($disposition, fn ($q) => $q->where('disposition_id', $disposition))
            ->when($category, fn ($q) => $q->whereIn('disposition_id', Disposition::where('category', $category->value)->select('id')));

        return view('technicians.appointments', [
            'technician' => $technician, 'month' => $month, 'segment' => $segment,
            'filter' => $disposition ? Disposition::find($disposition)?->name : $category?->label(),
            'appointments' => $query->orderBy('appointment_date')->orderBy('appointment_time')->paginate(100)->withQueryString(),
        ]);
    }

    /** A technician's upcoming appointments: today, this week, this month and later. */
    public function diary(Technician $technician)
    {
        $today = CarbonImmutable::today();
        $upcoming = fn () => Appointment::where('technician_id', $technician->id)
            ->where('disposition_id', Disposition::idFor(Disposition::UPCOMING))
            ->with(['client', 'location'])->orderBy('appointment_date')->orderBy('appointment_time');

        return view('technicians.diary', [
            'technician' => $technician,
            'tabs' => [
                'Today' => $upcoming()->whereDate('appointment_date', $today)->get(),
                'This week' => $upcoming()->between($today->startOfWeek()->toDateString(), $today->endOfWeek()->toDateString())->get(),
                'This month' => $upcoming()->between($today->startOfMonth()->toDateString(), $today->endOfMonth()->toDateString())->get(),
                'Later' => $upcoming()->whereDate('appointment_date', '>', $today->endOfMonth())->get(),
            ],
        ]);
    }

    /** Follow-up call lists: no-shows and "to be contacted" after two days, second calls after three. */
    public function calls(Request $request)
    {
        $business = $this->clinic($request);
        $now = CarbonImmutable::now();
        $first = Disposition::idsFor(Disposition::TO_BE_CONTACTED, Disposition::DID_NOT_SHOW);
        $second = Disposition::idsFor(Disposition::CALLED_ONCE);
        $list = fn (array $dispositions) => Appointment::whereIn('disposition_id', $dispositions)
            ->whereIn('client_id', Client::select('id')->where('business_id', $business->id))
            ->with(['client', 'technician', 'disposition'])->orderBy('status_updated_at');

        $lists = [
            'First call due' => $list($first)->whereBetween('status_updated_at', [$now->subMonth(), $now->subDays(2)])->get(),
            'Second call due' => $list($second)->whereBetween('status_updated_at', [$now->subMonth(), $now->subDays(3)])->get(),
            'Not due yet (updated in the last 2 days)' => $list($first)->where('status_updated_at', '>', $now->subDays(2))->get(),
            'Called once in the last 3 days' => $list($second)->where('status_updated_at', '>', $now->subDays(3))->get(),
        ];

        // Latest note per customer, in one query for all four lists.
        $clientIds = collect($lists)->flatten()->pluck('client_id')->filter()->unique();
        $latestNotes = Note::whereIn('id', Note::where('notable_type', 'client')->whereIn('notable_id', $clientIds)
            ->selectRaw('max(id)')->groupBy('notable_id'))->pluck('body', 'notable_id');

        return view('technicians.calls', ['business' => $business, 'lists' => $lists, 'latestNotes' => $latestNotes]);
    }

    private function clinic(Request $request): Business
    {
        $business = $request->user()->accessibleBusinesses()->firstWhere('is_clinic', true);
        abort_unless($business, 403);

        return $business;
    }
}
