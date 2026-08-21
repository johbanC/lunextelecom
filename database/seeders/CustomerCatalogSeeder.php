<?php

namespace Database\Seeders;

use App\Models\TicketType;
use Database\Seeders\Concerns\BuildsTicketCatalog;
use Illuminate\Database\Seeder;

/**
 * Catálogo completo de Customer Ticket — docs/SPEC_DESARROLLO.md sección 5.
 */
class CustomerCatalogSeeder extends Seeder
{
    use BuildsTicketCatalog;

    public function run(): void
    {
        $customer = TicketType::where('code', TicketType::CUSTOMER)->firstOrFail();

        $this->seedCategory($customer, 'Customer POS', $this->customerPosIssues());
        $this->seedCategory($customer, 'GA COAM Account Inquiry', $this->gaCoamIssues());
        $this->seedCategory($customer, 'ACCOUNTING INQUIRY', $this->accountingInquiryIssues(), 'Accounting');
        $this->seedCategory($customer, 'CONECT2 INQUIRY', $this->conect2InquiryIssues());
        $this->seedCategory($customer, 'TOP UP INQUIRY', $this->topUpInquiryIssues());
        $this->seedCategory($customer, 'COMPLAINT', $this->complaintIssues());
        $this->seedCategory($customer, 'GENERAL INQUIRY', $this->generalInquiryIssues());
    }

    /**
     * Casi todos los issues de Customer agregan "Retailer" (texto libre)
     * como primer campo del bloque dinámico (spec sección 5, intro).
     */
    private function withRetailer(array $fields = []): array
    {
        return [$this->retailerFreeText(), ...$fields];
    }

    private function customerPosIssues(): array
    {
        return [
            ['Bonus discrepancy', $this->withRetailer()],
            ['Calling instruction Assistance', $this->withRetailer([
                $this->text('Customer Name'), $this->callerNumber(), $this->text('Destination #'),
            ])],
            ['Can not Call/Connect', $this->withRetailer([
                $this->text('Customer Name'), $this->callerNumber(), $this->text('Destination #'),
            ])],
            ['Drop Call', $this->withRetailer()],
            ['eGift did not go through', $this->withRetailer($this->eGiftFields())],
            ['Fraud case-Balance removed', $this->withRetailer()],
            ['International Recharge has not gone through', $this->withRetailer([
                $this->callerName(), $this->callerNumber(),
                $this->text('Transaction ID'), $this->text('Transaction Status'), $this->text('Transaction Date/Time'),
                $this->text('Destination Country'), $this->text('Operator'), $this->text('Topup Phone'),
                $this->infoProvidedToCaller(),
            ])],
            ['Need recharge confirmation', $this->withRetailer([
                $this->callerName(), $this->callerNumber(), $this->text('Destination Country'), $this->text('Operator'), $this->text('Topup Phone'),
            ])],
            ['No confirmation received', $this->withRetailer($this->eGiftFields())],
            ['Other', $this->withRetailer([$this->textarea('Other Issue')])],
            ['Processed twice', $this->withRetailer([
                $this->callerName(), $this->callerNumber(), $this->text('Transaction ID'),
                $this->text('Destination Country'), $this->text('Operator'), $this->text('Topup Phone'),
                $this->infoProvidedToCaller(),
            ])],
            ['Quality Issue', $this->withRetailer()],
            ['Recharge assistance', $this->withRetailer([
                $this->callerName(), $this->callerNumber(), $this->text('Operator'), $this->text('PIN/RTR?'),
                $this->text('Topup phone'), $this->infoProvidedToCaller(),
            ])],
            ['Recipient did not receive egift', $this->withRetailer($this->eGiftFields())],
        ];
    }

    private function eGiftFields(): array
    {
        return [
            $this->text('Receive Phone Number'),
            $this->text('Sender Phone Number'),
            $this->text('Transaction ID'),
            $this->text('Transaction Status'),
            $this->text('Transaction Date/Time'),
            $this->text('Operator'),
            $this->infoProvidedToCaller(),
        ];
    }

    /**
     * Formulario de onboarding aparte — no lleva base de Caller Name/Caller#/
     * Method of Verification ni el campo Retailer (spec sección 5).
     */
    private function gaCoamIssues(): array
    {
        return [
            ['Sign Up Inquiry', [
                $this->text("Owner's Name"),
                $this->text("Owner's Cell Phone Number"),
                $this->text('Store Phone Number'),
                $this->text('Best time to call back'),
                $this->text('Primary E-mail Address'),
                $this->text('Store Name/DBA'),
                $this->text('Store Address'),
                $this->text('Company/Business Name'),
                $this->text('Location Licence Holder (LLH#)'),
                $this->text('Master Licence Holder (MLH) Name'),
                $this->text('How did you hear about us?'),
                $this->textarea('Additional Notes'),
            ]],
            ['Follow up/call back', []],
            ['New Ownership', []],
            ['OTHER', []],
        ];
    }

    private function accountingInquiryIssues(): array
    {
        return [
            ['Add Minutes', $this->withRetailer([
                $this->callerName(), $this->callerNumber(), $this->methodOfVerificationPick2(),
                $this->text('Balance'), $this->text('Number of Refill/Cycle'),
                $this->infoProvidedToCaller(), $this->followUp(), $this->notes(),
            ])],
            ['CC-Status', $this->withRetailer([
                $this->callerName(), $this->callerNumber(),
                $this->radio('Credit Card Status', ['Decline', 'No Match', 'Zip Match', 'Address Match']),
                $this->text('# of tries'), $this->text('1st/2nd/3rd try status'),
                $this->infoProvidedToCaller(), $this->followUp(), $this->notes(),
            ])],
            ['HOLD Balance', $this->withRetailer([
                $this->callerNumber(), $this->callerName(), $this->retailerLogin(),
                $this->select('Method of Verification', [
                    'User ID', 'Caller Name', 'Phone Number', 'Address', 'Last 4 digit of Bank Account',
                ]),
                $this->text('Hold Amount'), $this->textarea('Reason'), $this->notes(),
            ])],
            ['Refund-Fraud call', $this->withRetailer([
                $this->callerName(), $this->callerNumber(), $this->retailerLogin(), $this->methodOfVerificationPick2(),
                $this->text('Allowed Destination Country'), $this->text('Allowed Destination #'),
                $this->text('Fraud Destination Country'), $this->text('Fraud Destination #'),
                $this->date('Issue Date'), $this->text('Refund Amount'),
                $this->infoProvidedToCaller(), $this->followUp(), $this->notes(),
            ])],
            ['Refund-CC', $this->withRetailer([
                $this->callerName(), $this->callerNumber(), $this->retailerLogin(), $this->methodOfVerificationPick2(),
                $this->date('Transaction Date'), $this->text('Transaction ID'), $this->text('Voided Amount'),
                $this->text('CC last 4 digits'),
                $this->radio('Credit Card Status', ['Decline', 'No Match', 'Zip Match', 'Address Match', 'Other']),
                $this->notes(),
            ])],
            ['Fraud Case-Balance removed', $this->withRetailer([
                $this->text('Customer Name'), $this->callerNumber(), $this->retailerLogin(), $this->methodOfVerificationPick2(),
                $this->checkbox('Suspicious Activity', [
                    'Unusual Call Country', 'Unusual Credit Balance consumed', 'Unusual Call Frequency',
                    'Different Credit Cards provided', 'Credit card status not Exact Match',
                ]),
                $this->notes(),
            ])],
            ['Back-Charge', $this->withRetailer([
                $this->text('Customer Name'), $this->callerNumber(), $this->retailerLogin(),
                $this->date('Transaction Date'), $this->text('Transaction ID'), $this->text('Amount'),
                $this->text('Credit Card Last 4 digits'), $this->infoProvidedToCaller(), $this->followUp(), $this->notes(),
            ])],
        ];
    }

    private function conect2InquiryIssues(): array
    {
        return [
            ['DID/Access Number', $this->withRetailer([
                $this->text('Customer Name'), $this->callerNumber(), $this->text('Issue Date/Time'), $this->text('Option'),
                $this->select('Issue Description', ['Silence']),
                $this->followUp(),
                $this->text('Caller Phone Carrier/Operator'), $this->text('DID/TFN'), $this->textarea('Test Results'),
                $this->infoProvidedToCaller(), $this->notes(),
            ])],
            ['Destination', $this->withRetailer([
                $this->text('Customer Name'), $this->callerNumber(), $this->text('DID/Access #'), $this->text('Destination #'),
                $this->text('Issue Date/Time'),
                $this->select('Issue Description', ['Static, Noise, Distortion']),
                $this->textarea('Test Results DID + Destination #'),
                $this->infoProvidedToCaller(), $this->followUp(), $this->notes(),
            ])],
            ['Speed Dial Number / 1Clic # Add/Edit', $this->withRetailer([
                $this->text('Customer Name'), $this->callerNumber(), $this->text('Contact Added'), $this->text('Country Added'), $this->notes(),
            ])],
            ['DID/Dialing Instructions', $this->withRetailer([
                $this->text('Customer Name'), $this->callerNumber(), $this->text('DID #'), $this->text('Speed Dial/1Clic #'), $this->notes(),
            ])],
            ['Possible Hang Call', $this->withRetailer([
                $this->callerName(), $this->callerNumber(), $this->text('DID/TFN'), $this->text('Estimated Date/Time'),
                $this->textarea('IVR Message'), $this->notes(),
            ])],
            ['Refund-Test Call', $this->withRetailer([
                $this->text('Account #'), $this->text('Amount'), $this->textarea('Reason'), $this->notes(),
            ])],
            ['Bonus discrepancy', $this->withRetailer([
                $this->callerName(), $this->callerNumber(), $this->text('SMS promotion'), $this->date('Date'),
                $this->text('Amount'), $this->infoProvidedToCaller(), $this->followUp(), $this->notes(),
            ])],
            ['Unlimited Plan', $this->withRetailer([
                $this->callerName(), $this->callerNumber(), $this->text('Product (SKU)'), $this->textarea('Issue'),
                $this->infoProvidedToCaller(), $this->followUp(), $this->notes(),
            ])],
            ['Account Expired', $this->withRetailer([
                $this->text('Customer Name'), $this->callerNumber(), $this->text('DID #'), $this->date('Expiration Date'),
                $this->text('Expire Amount'), $this->text('Method of Payment'),
                $this->radio('Expiration SMS Notification', ['Yes', 'No']), $this->text('When?'),
                $this->infoProvidedToCaller(), $this->followUp(), $this->notes(),
            ])],
            ['SECURITY CODE INQUIRY', $this->withRetailer([
                $this->text('Customer Name'), $this->callerNumber(), $this->text('DID #'), $this->textarea('Message after dialing DID'),
                $this->infoProvidedToCaller(), $this->followUp(), $this->notes(),
            ])],
        ];
    }

    private function topUpInquiryIssues(): array
    {
        $topUpBase = fn () => [
            $this->callerName(), $this->callerNumber(), $this->retailerLogin(),
            $this->text('Transaction ID'), $this->text('Transaction Status'), $this->text('Transaction Date/Time'),
        ];

        return [
            ['International TopUp', $this->withRetailer([
                ...$topUpBase(),
                $this->text('Destination Country'), $this->text('Operator'), $this->text('Topup Phone'),
                $this->select('Issue Description', ["Beneficiary didn't received credit"]),
                $this->infoProvidedToCaller(), $this->followUp(), $this->notes(),
            ])],
            ['Domestic TopUp', $this->withRetailer([
                ...$topUpBase(),
                $this->text('Operator'), $this->text('Topup Phone'),
                $this->select('Issue Description', ["Beneficiary didn't received credit"]),
                $this->infoProvidedToCaller(), $this->followUp(), $this->notes(),
            ])],
            ['GA COAM', $this->withRetailer([
                ...$topUpBase(),
                $this->textarea('Issue Description'), $this->infoProvidedToCaller(), $this->notes(),
            ])],
            ['PINLESS/BOSS & SIN PIN', $this->withRetailer([
                ...$topUpBase(),
                $this->text('Product (SKU)'), $this->text('TopUp Phone'),
                $this->select('Issue Description', ['Incorrect Product']),
                $this->infoProvidedToCaller(), $this->followUp(), $this->notes(),
            ])],
            ['RELOADABLE CARD', $this->withRetailer([
                ...$topUpBase(),
                $this->text('Product (SKU)'), $this->text('TopUp Phone'),
                $this->select('Issue Description', ['Incorrect Product']),
                $this->infoProvidedToCaller(), $this->followUp(), $this->notes(),
            ])],
            // Pendiente de confirmar (spec sección 11.3): sin captura detallada,
            // se usa el patrón de issues similares hasta confirmar con Lunex.
            ['E-Gift', $this->withRetailer([
                ...$topUpBase(),
                $this->textarea('Issue Description'), $this->infoProvidedToCaller(), $this->notes(),
            ])],
        ];
    }

    private function complaintIssues(): array
    {
        $fields = fn () => $this->withRetailer([
            $this->callerName(), $this->callerNumber(), $this->retailerLogin(), $this->notes(),
        ]);

        return [
            ['Others', $fields()],
            ['Promotion', $fields()],
            ['Rate', $fields()],
        ];
    }

    private function generalInquiryIssues(): array
    {
        $minimal = fn () => $this->withRetailer([$this->callerName(), $this->retailerLogin(), $this->notes()]);

        return [
            // Pendiente de confirmar (spec sección 11.3): visto en el dropdown
            // del sistema viejo sin captura de formulario completo.
            ['Balance Inquiry', $this->withRetailer([$this->callerName(), $this->callerNumber(), $this->notes()])],
            ['Follow Up/Call back', $this->withRetailer([
                $this->callerName(), $this->callerNumber(), $this->retailerLogin(),
                $this->textarea('Reason'), $this->textarea('Result'), $this->notes(),
            ])],
            ['New customer', $this->withRetailer([$this->callerName(), $this->callerNumber()])],
            ['Others', $minimal()],
            ['Promotion', $minimal()],
            ['Rate', $minimal()],
            ['Connect2 Sale', $this->withRetailer([
                $this->callerName(), $this->callerNumber(), $this->retailerLogin(), $this->text('Product (SKU)'),
                $this->text('Transaction ID'), $this->text('Transaction Status'), $this->text('Transaction Date/Time'),
                $this->text('Credit Card Estatus'), $this->text('Credit Card Last 4 digits'), $this->notes(),
            ])],
            ['ITU Sale', $this->withRetailer([
                $this->callerName(), $this->callerNumber(),
                $this->pickN('Method of Verification', 2, ['ITU Sale Verification Docs', "Previous ITU's Sale", 'Other']),
                $this->text('Transaction ID'), $this->text('Transaction Status'), $this->text('Transaction Date/Time'),
                $this->text('Country/Operator'), $this->text('Credit Card Estatus'), $this->text('Credit Card Last 4 digits'),
                $this->notes(),
            ])],
            ['Subscribe/Unsub. SMS', $this->withRetailer([
                $this->callerName(), $this->callerNumber(),
                $this->text('Would like to Subscribe #'), $this->text('Would like to Unsubscribe #'),
                $this->text('Label/Product'),
                $this->checkbox('SMS Issue', [
                    'Received Incorrect information', "Didn't receive Confirmation text", 'Promotional Information', 'Other',
                ]),
                $this->notes(),
            ])],
            ['Transfer Call', $this->withRetailer([
                $this->callerName(), $this->callerNumber(), $this->text('Transfer To'), $this->notes(),
            ])],
            ['Update Account Info', $this->withRetailer([
                $this->callerName(), $this->callerNumber(), $this->methodOfVerificationPick2(), $this->notes(),
            ])],
            ['CALL BACK / FOLLOW UP', $this->withRetailer([
                $this->callerName(), $this->text('Caller Contact Phone #'), $this->text('Extension'),
                $this->text('Best Time to Call Back'), $this->text('Company Name'), $this->text('Reference to'), $this->notes(),
            ])],
        ];
    }
}
