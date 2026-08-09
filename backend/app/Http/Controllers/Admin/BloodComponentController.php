<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BloodUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BloodComponentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $group = (string) $request->query('group', 'all');
        $processing = (string) $request->query('processing', 'all');
        $bloodGroups = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];

        if (! in_array($group, $bloodGroups, true)) {
            $group = 'all';
        }

        if (! in_array($processing, ['processed', 'partial', 'unprocessed'], true)) {
            $processing = 'all';
        }

        $parents = BloodUnit::query()
            ->where('component_type', 'whole_blood')
            ->with(['donation','components.allocations','components.components.parent'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('unit_number', 'like', '%'.$search.'%')
                        ->orWhereHas('donation', fn ($donation) => $donation->where('reference', 'like', '%'.$search.'%'));
                });
            })
            ->when($group !== 'all', fn ($query) => $query->where('blood_group', $group))
            ->when($processing === 'processed', fn ($query) => $query->has('components', '>=', 3))
            ->when($processing === 'partial', fn ($query) => $query->has('components')->has('components', '<', 3))
            ->when($processing === 'unprocessed', fn ($query) => $query->whereDoesntHave('components'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.national.components', compact('parents', 'search', 'group', 'processing', 'bloodGroups'));
    }

    public function store(Request $request, BloodUnit $unit): RedirectResponse
    {
        $data = $request->validate([
            'components' => ['required','array','min:1'],
            'components.*' => ['required', Rule::in(['red_cells','plasma','platelets','cryoprecipitate'])],
            'modifiers' => ['nullable','array'],
            'modifiers.*' => ['nullable','array'],
            'modifiers.*.leukoreduced' => ['nullable','boolean'],
            'modifiers.*.irradiated' => ['nullable','boolean'],
            'modifiers.*.washed' => ['nullable','boolean'],
            'location' => ['required','string','max:120'],
        ]);

        DB::transaction(function () use ($unit, $data): void {
            $parent = BloodUnit::query()->lockForUpdate()->findOrFail($unit->id);
            $primaryTypes = collect(['red_cells', 'plasma', 'platelets']);
            $existingPrimaryTypes = $parent->components()->whereIn('component_type', $primaryTypes->all())->pluck('component_type');
            $legacyPartial = $parent->status === 'used' && $existingPrimaryTypes->isNotEmpty() && $existingPrimaryTypes->count() < $primaryTypes->count();
            $primaryProcessable = in_array($parent->status, ['available', 'processing'], true) || $legacyPartial;
            $requestedTypes = collect(array_unique($data['components']));
            $requestedPrimary = $requestedTypes->intersect($primaryTypes);
            $wantsCryo = $requestedTypes->contains('cryoprecipitate');

            if ($parent->component_type !== 'whole_blood' || ! $parent->released_at) {
                throw ValidationException::withMessages(['components' => __('bloodcare.national.components.validation_unavailable')]);
            }

            if ($requestedPrimary->isNotEmpty() && (! $primaryProcessable || $parent->expires_at->isPast())) {
                throw ValidationException::withMessages(['components' => __('bloodcare.national.components.validation_unavailable')]);
            }

            $expiryDays = ['red_cells'=>42,'plasma'=>365,'platelets'=>5,'cryoprecipitate'=>365];
            $baseNumber = substr($parent->unit_number, 0, 34);
            $newPrimaryTypes = $requestedPrimary->reject(fn (string $type) => $existingPrimaryTypes->contains($type));

            $plasma = $parent->components()->where('component_type', 'plasma')->first();
            $existingCryo = $plasma?->components()->where('component_type', 'cryoprecipitate')->exists() ?? false;

            if ($wantsCryo && ! $plasma) {
                throw ValidationException::withMessages(['components' => __('bloodcare.national.components.validation_cryo_requires_plasma')]);
            }

            if ($wantsCryo && ($existingCryo || $plasma->status !== 'available' || ! $plasma->released_at || $plasma->expires_at->isPast() || $plasma->allocations()->exists())) {
                throw ValidationException::withMessages(['components' => $existingCryo
                    ? __('bloodcare.national.components.validation_already_created')
                    : __('bloodcare.national.components.validation_cryo_source_unavailable')]);
            }

            if ($newPrimaryTypes->isEmpty() && ! $wantsCryo) {
                throw ValidationException::withMessages(['components' => __('bloodcare.national.components.validation_already_created')]);
            }

            foreach ($newPrimaryTypes as $type) {
                $componentExpiry = $parent->collected_at->copy()->addDays($expiryDays[$type]);
                if ($componentExpiry->lt(today())) {
                    throw ValidationException::withMessages([
                        'components' => __('bloodcare.national.components.validation_expired_component', [
                            'component' => __('bloodcare.national.components.types.'.$type),
                        ]),
                    ]);
                }

                $child = BloodUnit::create([
                    'unit_number' => $baseNumber.'-'.match($type){'red_cells'=>'RBC','plasma'=>'PLS','platelets'=>'PLT'},
                    'donation_id'=>$parent->donation_id,'parent_blood_unit_id'=>$parent->id,'blood_group'=>$parent->blood_group,
                    'component_type'=>$type,'collected_at'=>$parent->collected_at,
                    'expires_at'=>$componentExpiry,'storage_location'=>trim($data['location']),
                    'status'=>'available','released_at'=>$parent->released_at,'released_by'=>$parent->released_by,
                    'leukoreduced'=>(bool) data_get($data, "modifiers.{$type}.leukoreduced", false),
                    'irradiated'=>(bool) data_get($data, "modifiers.{$type}.irradiated", false),
                    'washed'=>(bool) data_get($data, "modifiers.{$type}.washed", false),
                    'notes'=>'Component prepared from '.$parent->unit_number.'.',
                ]);
                ActivityLog::record(['type'=>'Processing','action'=>'Blood component created','subject_type'=>BloodUnit::class,'subject_id'=>$child->id,'donor_id'=>$parent->donation?->donor_id,'user_id'=>backpack_user()?->id,'result'=>'Available','details'=>"{$child->unit_number}; {$type}",'source'=>'admin-components']);
            }

            if ($wantsCryo) {
                $componentExpiry = $parent->collected_at->copy()->addDays($expiryDays['cryoprecipitate']);
                if ($componentExpiry->lt(today())) {
                    throw ValidationException::withMessages([
                        'components' => __('bloodcare.national.components.validation_expired_component', [
                            'component' => __('bloodcare.national.components.types.cryoprecipitate'),
                        ]),
                    ]);
                }

                $cryo = BloodUnit::create([
                    'unit_number'=>$baseNumber.'-CRYO','donation_id'=>$parent->donation_id,'parent_blood_unit_id'=>$plasma->id,
                    'blood_group'=>$parent->blood_group,'component_type'=>'cryoprecipitate','collected_at'=>$parent->collected_at,
                    'expires_at'=>$componentExpiry,'storage_location'=>trim($data['location']),'status'=>'available',
                    'released_at'=>$parent->released_at,'released_by'=>$parent->released_by,
                    'leukoreduced'=>false,'irradiated'=>false,'washed'=>false,
                    'notes'=>'Cryoprecipitate prepared from frozen plasma '.$plasma->unit_number.'.',
                ]);
                $plasma->update(['status'=>'used','notes'=>'Used as the plasma source for '.$cryo->unit_number.'.']);
                ActivityLog::record(['type'=>'Processing','action'=>'Cryoprecipitate created from plasma','subject_type'=>BloodUnit::class,'subject_id'=>$cryo->id,'donor_id'=>$parent->donation?->donor_id,'user_id'=>backpack_user()?->id,'result'=>'Available','details'=>"{$cryo->unit_number}; plasma {$plasma->unit_number}",'source'=>'admin-components']);
            }

            $componentCount = $parent->components()->whereIn('component_type', $primaryTypes->all())->count();
            $parent->update([
                'status' => $componentCount >= 3 ? 'used' : 'processing',
                'notes' => $componentCount >= 3
                    ? 'Fully processed into blood components; parent whole-blood unit is no longer allocatable.'
                    : 'Partially processed into blood components; parent whole-blood unit is reserved for remaining component processing.',
            ]);
        });

        return back()->with('status', __('bloodcare.national.components.saved'));
    }
}
