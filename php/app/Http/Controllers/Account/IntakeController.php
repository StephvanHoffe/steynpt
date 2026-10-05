<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Intake;
use App\Services\Plans\Generator;
use App\Support\Intake as IntakeRules;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Intake van de klant: basis voor het trainings- en voedingsschema. */
class IntakeController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $row = Intake::query()->find($user->id);
        [$initial] = $row ? IntakeRules::validate($row->data ?? []) : [null];

        return view('account.intake', ['user' => $user, 'exists' => (bool) $row, 'initial' => $initial]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        [$data, $errors] = IntakeRules::validate(IntakeRules::intakeFromFormData($request->except('_token')));
        $consent = $request->input('consent') === 'on';
        if (! $data || ! $consent) {
            if (! $consent) {
                $errors['consent'] = 'Geef toestemming om je gegevens te gebruiken voor je schema';
            }

            return back()->withErrors($errors, 'intake')->withInput()->with('intake_error', 'Controleer de gemarkeerde velden.');
        }

        $now = CarbonImmutable::now('UTC');
        $row = Intake::query()->find($user->id);
        if ($row) {
            $row->forceFill(['data' => $data, 'updated_at' => $now])->save();
        } else {
            Intake::query()->create(['user_id' => $user->id, 'data' => $data, 'created_at' => $now, 'updated_at' => $now]);
        }
        $user->forceFill(['goal' => $data['goal']])->save();

        $generating = false;
        if (Generator::mayAutoGenerate($user)) {
            foreach ($data['wants'] as $type) {
                Generator::createPlanJob($user->id, $type);
            }
            $generating = true;
        }

        return redirect('/account?intake='.($generating ? 'gestart' : 'opgeslagen'));
    }
}
