<?php

namespace App\Livewire;

use App\Enums\Channel;
use App\Models\Business;
use App\Models\ChannelSource;
use App\Models\LeadSource;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Which lead sources count towards each paid channel in a business's marketing reports. */
class ChannelSourceManager extends Component
{
    #[Locked]
    public int $businessId;

    public function mount(Business $business): void
    {
        $this->businessId = $business->id;
    }

    public function toggle(string $channel, int $sourceId): void
    {
        abort_unless(auth()->user()->canManage() && auth()->user()->canAccessBusiness($this->businessId), 403);
        abort_unless(Channel::tryFrom($channel) && LeadSource::whereKey($sourceId)->exists(), 404);

        $attributes = ['business_id' => $this->businessId, 'channel' => $channel, 'lead_source_id' => $sourceId];
        ChannelSource::where($attributes)->exists()
            ? ChannelSource::where($attributes)->delete()
            : ChannelSource::create($attributes);
    }

    public function render()
    {
        return view('livewire.channel-source-manager', [
            'channels' => Channel::cases(),
            'sources' => LeadSource::orderBy('name')->get(),
            'map' => Business::findOrFail($this->businessId)->channelSourceIds(),
        ]);
    }
}
