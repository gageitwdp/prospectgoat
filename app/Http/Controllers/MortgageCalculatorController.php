<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMortgageCalculatorLeadRequest;
use App\Mail\MortgageCalculatorResultsMail;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MortgageCalculatorController extends Controller
{
    public function index()
    {
        return view('mortgage.calculator');
    }

    public function sendResults(StoreMortgageCalculatorLeadRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $inputs = $this->normalizedInputs($data);
        $results = $this->calculateMortgage($inputs);

        $lead = Lead::create([
            'name' => $inputs['full_name'],
            'email' => $inputs['email'],
            'phone' => $inputs['phone'] ?: null,
            'address' => null,
            'lead_type' => 'buyer',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $lead->activities()->create([
            'type' => 'note',
            'description' => sprintf(
                'Mortgage calculator request captured. Home price: $%s, down payment: $%s, rate: %s%%, term: %s years, estimated monthly total: $%s.',
                number_format($inputs['home_price'], 2),
                number_format($inputs['down_payment'], 2),
                number_format($inputs['annual_interest_rate'], 3),
                (int) $inputs['loan_term_years'],
                number_format($results['total_monthly_payment'], 2)
            ),
        ]);

        try {
            Mail::to($inputs['email'])->send(new MortgageCalculatorResultsMail($inputs['full_name'], $inputs, $results));
        } catch (Throwable $exception) {
            Log::error('Mortgage calculator results email failed.', [
                'lead_id' => $lead->id,
                'lead_email' => $inputs['email'],
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('calculator_error', 'Your lead was saved, but we could not send the results email right now. Please try again.');
        }

        return back()->with('calculator_success', 'Results sent. Check your inbox for your mortgage estimate.');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function normalizedInputs(array $data): array
    {
        return [
            'full_name' => trim((string) $data['full_name']),
            'email' => strtolower(trim((string) $data['email'])),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'home_price' => (float) $data['home_price'],
            'down_payment' => (float) $data['down_payment'],
            'annual_interest_rate' => (float) $data['annual_interest_rate'],
            'loan_term_years' => (int) $data['loan_term_years'],
            'property_tax_rate' => (float) ($data['property_tax_rate'] ?? 1.2),
            'home_insurance_yearly' => (float) ($data['home_insurance_yearly'] ?? 1200),
            'hoa_monthly' => (float) ($data['hoa_monthly'] ?? 0),
            'pmi_monthly' => (float) ($data['pmi_monthly'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $inputs
     * @return array<string, float>
     */
    protected function calculateMortgage(array $inputs): array
    {
        $principal = max(0, (float) $inputs['home_price'] - (float) $inputs['down_payment']);
        $loanTermMonths = max(1, (int) $inputs['loan_term_years'] * 12);
        $monthlyRate = ((float) $inputs['annual_interest_rate'] / 100) / 12;

        if ($monthlyRate <= 0) {
            $principalInterestMonthly = $principal / $loanTermMonths;
        } else {
            $growth = pow(1 + $monthlyRate, $loanTermMonths);
            $principalInterestMonthly = $principal * (($monthlyRate * $growth) / ($growth - 1));
        }

        $propertyTaxMonthly = ((float) $inputs['home_price'] * ((float) $inputs['property_tax_rate'] / 100)) / 12;
        $homeInsuranceMonthly = (float) $inputs['home_insurance_yearly'] / 12;
        $hoaMonthly = (float) $inputs['hoa_monthly'];
        $pmiMonthly = (float) $inputs['pmi_monthly'];

        return [
            'loan_amount' => round($principal, 2),
            'principal_interest_monthly' => round($principalInterestMonthly, 2),
            'property_tax_monthly' => round($propertyTaxMonthly, 2),
            'home_insurance_monthly' => round($homeInsuranceMonthly, 2),
            'hoa_monthly' => round($hoaMonthly, 2),
            'pmi_monthly' => round($pmiMonthly, 2),
            'total_monthly_payment' => round($principalInterestMonthly + $propertyTaxMonthly + $homeInsuranceMonthly + $hoaMonthly + $pmiMonthly, 2),
        ];
    }
}
