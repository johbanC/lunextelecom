<?php

namespace Database\Seeders;

use App\Models\TicketType;
use Database\Seeders\Concerns\BuildsTicketCatalog;
use Illuminate\Database\Seeder;

/**
 * Catálogo completo de Retailer Ticket — docs/SPEC_DESARROLLO.md sección 4.
 */
class RetailerCatalogSeeder extends Seeder
{
    use BuildsTicketCatalog;

    public function run(): void
    {
        $retailer = TicketType::where('code', TicketType::RETAILER)->firstOrFail();

        $this->seedCategory($retailer, 'R Account inquiry', $this->accountInquiryIssues(), 'Accounting');
        $this->seedCategory($retailer, 'R Retailer POS', $this->retailerPosIssues());
        $this->seedCategory($retailer, 'R Retailer Services', $this->retailerServicesIssues());
    }

    /**
     * Campos base comunes a casi todos los issues de "R Account inquiry".
     */
    private function accountInquiryBase(): array
    {
        return [$this->callerName(), $this->callerNumber(), $this->methodOfVerificationPick2()];
    }

    private function accountInquiryIssues(): array
    {
        return [
            ['Account Reactivation/Status', [
                ...$this->accountInquiryBase(),
                $this->text('Entity Name'),
                $this->notes(),
            ]],
            ['ACH for Extra Credit', [
                ...$this->accountInquiryBase(),
                $this->text('Current Credit'),
                $this->text('ACH & Refill Amount'),
                $this->notes(),
            ]],
            ['Add New Product', [
                ...$this->accountInquiryBase(),
                $this->text('Product Request (SKU)'),
                $this->notes(),
            ]],
            ['Check Sales Report/Send Invoice', [
                ...$this->accountInquiryBase(),
                $this->text('Product Request (Name & SKU)'),
                $this->date('From Date'),
                $this->date('To Date'),
                $this->notes(),
            ]],
            ['Conect2-Pinless Void', [
                ...$this->accountInquiryBase(),
                $this->text('Customer #'),
                $this->date('Transaction Date'),
                $this->text('Transaction Amount'),
                $this->text('Void Reason'),
                $this->notes(),
            ]],
            ['Credit Balance Update', [
                ...$this->accountInquiryBase(),
                $this->text('Current Credit Balance'),
                $this->notes(),
            ]],
            ['Others', [
                ...$this->accountInquiryBase(),
                $this->textarea('Issue Description'),
                $this->notes(),
            ]],
            ['Password Reset', [
                ...$this->accountInquiryBase(),
                $this->text('Product Request (SKU)'),
                $this->radio('Entity Type', [
                    'Special Distributor', 'Super Retailer', 'Authorized API', 'Full Integration', 'Others',
                ]),
                $this->checkbox('Sent Password To', ['Phone Number', 'Email Address']),
                $this->notes(),
            ]],
            ['TopUp Noc Inquiry', [
                ...$this->accountInquiryBase(),
                $this->radio('Key Account', ['Yes', 'No']),
                $this->date('Transaction Date'),
                $this->text('Transaction ID'),
                $this->text('Country'),
                $this->text('Carrier'),
                $this->text('TopUp Phone'),
                $this->text('US Phone'),
                $this->text('Amount'),
                $this->textarea('Reason'),
                $this->notes(),
            ]],
            ['Update Info', [
                ...$this->accountInquiryBase(),
                $this->textarea('Update Request'),
                $this->textarea('NEW info'),
                $this->notes(),
            ]],
        ];
    }

    /**
     * Nota (spec sección 4): catálogo incompleto en el Excel de Maritza —
     * confirmar si hay más issues además de "Login Issues".
     */
    private function retailerPosIssues(): array
    {
        return [
            ['Login Issues', [
                $this->callerName(),
                $this->callerNumber(),
                $this->text('User ID'),
                $this->methodOfVerificationPick2(),
                $this->radio('Issues', [
                    'Did not receive Verification Code',
                    "Verification Code was input but still can't login",
                    "Correct Password but still can't login",
                    'Oth',
                ]),
                $this->radio('Screen-shot provided', ['Yes', 'No']),
                $this->notes(),
            ]],
        ];
    }

    private function retailerServicesIssues(): array
    {
        $base = fn () => [$this->callerName(), $this->callerNumber(), $this->methodOfVerificationPick2()];

        return [
            ['Order Material', [
                ...$base(),
                $this->text('USER ID'),
                $this->text('Store Name'),
                $this->text('Address'),
                $this->text('Ship To ID'),
                $this->checkbox('Material Request', [
                    'OMNY Cards', 'OMNY Marketing Materials', 'COAM Cards', 'COAM Marketing Materials', 'Others',
                ]),
                $this->text('Quantity'),
                $this->radio('Card Order placed', ['Yes', 'No']),
                $this->date('Card Order Date'),
                $this->notes(),
            ]],
            ['Others', [
                ...$base(),
                $this->textarea('Issue Description'),
                $this->notes(),
            ]],
            ['POS Issues', [
                $this->callerName(),
                $this->callerNumber(),
                $this->text('User ID'),
                $this->methodOfVerificationPick2(),
                $this->text('Product'),
                $this->text('OS/Browser Version'),
                $this->date('Issue Date'),
                $this->checkbox('Label', ['1ClicMax', 'PICA', 'Mega Minutos', 'Mas Minutos', 'Youtelo', 'SSO-MAXI']),
                $this->radio('Screen-shot provided', ['Yes', 'No']),
                $this->checkbox('Issue Description', [
                    'Retailer gets error message', 'Unable to display page', 'Account Restricted',
                    'Promotion not displayed', 'Others',
                ]),
                $this->textarea('Test Result'),
                $this->notes(),
            ]],
            ['Promotion', [
                ...$base(),
                $this->text('Product/Promo'),
                $this->textarea('Comment/Input'),
                $this->notes(),
            ]],
            ['Sale on Behalf', [
                ...$base(),
                $this->text('Customer #'),
                $this->text('Amount'),
                $this->textarea('Reason'),
                $this->notes(),
            ]],
            ['Training Inquiry', [
                ...$base(),
                $this->text('USER ID'),
                $this->text('Store Name'),
                $this->text('Address'),
                $this->date('Date Account was Opened'),
                $this->notes(),
            ]],
        ];
    }
}
