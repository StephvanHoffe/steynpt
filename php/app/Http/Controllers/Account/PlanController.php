<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Plans\Schedule;
use App\Support\Plans\PlanSchema;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Een schema bekijken (en printen) in Mijn omgeving. */
class PlanController extends Controller
{
    public function show(Request $request, string $id): View
    {
        // Klanten zien alleen hun eigen, door Steyn gepubliceerde schema's; een ingepland schema pas vanaf de startdag.
        Schedule::activateDuePlans();
        $plan = Plan::query()->whereKey($id)->where('user_id', $request->user()->id)->where('status', 'gepubliceerd')->first();
        abort_unless($plan, 404);
        [$content] = PlanSchema::parse($plan->type, $plan->content);
        abort_if($content === null, 404);

        return view('account.schema', ['plan' => $plan, 'content' => $content]);
    }
}
