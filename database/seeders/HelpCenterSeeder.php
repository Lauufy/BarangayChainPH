<?php

namespace Database\Seeders;

use App\Models\HelpCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HelpCenterSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['Permits & Clearances', 'bg-rose-500/20 text-rose-500', [
                [
                    'How to apply for a barangay clearance',
                    'Submit a clearance request from your verified resident account.',
                    true,
                    <<<'MARKDOWN'
                    1. Open **Services** and choose **Barangay Clearance**.
                    2. Select your purpose. Enter your contact number, the year you began living in Barangay San Luis, and the ID you presented.
                    3. Review the details and submit. Open **Applications** to follow your request.
                    MARKDOWN,
                ],
                [
                    'How to apply for a barangay business clearance',
                    'Prepare the registration details for a new business or a renewal.',
                    false,
                    <<<'MARKDOWN'
                    Open **Services → Barangay Business Clearance** and choose **New application** or **Renewal**. Enter the business name, address, owner, ownership type, activity, and contact number.

                    Prepare the DTI or SEC registration. For a new application, enter the paid-up capital. For a renewal, attach the previous barangay business clearance. Review the form before submitting.
                    MARKDOWN,
                ],
                [
                    'What you need for a barangay clearance',
                    'Gather the details the clearance form asks for.',
                    false,
                    <<<'MARKDOWN'
                    Start with a verified account. Have your contact number, year of residency, purpose, and name of the ID you presented ready. The voter registration number is optional.

                    If the receiving office specifically asks for a **Certificate of Residency**, choose that service. Its form asks for a valid government ID and proof of residency.
                    MARKDOWN,
                ],
                [
                    'How to track your permit request',
                    'Find your application status and see when staff need a response.',
                    false,
                    <<<'MARKDOWN'
                    Open [Applications](/citizen/applications) and select your request. **Pending** means it awaits review. **Processing** means staff are working on it. If you see **Resubmission required**, read the instructions and send the requested correction.

                    For an approved request, open [Documents](/citizen/documents). For a rejected request, read the reason before applying again.
                    MARKDOWN,
                ],
                [
                    'How to check barangay clearance fees',
                    'Get payment instructions for your specific request.',
                    false,
                    <<<'MARKDOWN'
                    Open the application and read any instructions from staff. If you need to confirm a fee, ask in the application ticket and include the request reference. Ask for the amount, where to pay, and how to obtain a receipt before making a payment. Keep the receipt with your reference number.
                    MARKDOWN,
                ],
                [
                    'How to save an approved barangay clearance',
                    'Find the issued PDF and keep a copy.',
                    false,
                    <<<'MARKDOWN'
                    1. Open [Documents](/citizen/documents) and find your approved clearance.
                    2. Choose **Get printable PDF** to use one copy from your allowance. Save the issued PDF from the viewer or print a paper copy.
                    3. Keep the original PDF for verification. If the print limit has been reached, contact staff with the application reference.
                    MARKDOWN,
                ],
                [
                    'How to renew a barangay permit or clearance',
                    'Choose the renewal option for a business clearance.',
                    false,
                    <<<'MARKDOWN'
                    For a business, open **Services → Barangay Business Clearance** and select **Renewal**. Enter the current business details and attach your registration and previous clearance. Submit and track the new request under **Applications**.

                    For a personal clearance, ask staff which service to use if the receiving office asks for a renewal.
                    MARKDOWN,
                ],
                [
                    'What to do if your application is rejected',
                    'Use the officer’s reason to decide your next step.',
                    false,
                    <<<'MARKDOWN'
                    Open the request and read the rejection reason. If a detail is unclear, ask staff through the Help Center and quote your application reference. Before filing a new request, confirm what needs to change.

                    If the status reads **Resubmission required**, open that application and use its resubmission action so staff receive the correction on the same request.
                    MARKDOWN,
                ],
                [
                    'How long does permit processing take?',
                    'Check progress and ask about a deadline.',
                    false,
                    <<<'MARKDOWN'
                    Open **Applications** for the current status and check **Notifications** for a request from staff. If staff need a correction, submitting it through the existing request lets the review continue.

                    If you have a deadline, give staff the application reference, submission date, and date you need the document. Staff can advise on timing for that request.
                    MARKDOWN,
                ],
                [
                    'What smart contracts do during permit processing',
                    'See where the blockchain fits into a staff-reviewed application.',
                    false,
                    <<<'MARKDOWN'
                    Barangay personnel review your application and decide whether to approve it. When an approved document is issued, the portal uses its blockchain registry to record information used for later verification. Revocation can also be recorded there.

                    For an application decision, check **Applications**. To check an issued file, use the public verification page.
                    MARKDOWN,
                ],
                [
                    'How blockchain helps protect permit records',
                    'Understand what the verification record can tell you.',
                    false,
                    <<<'MARKDOWN'
                    An issued permit has an identifying reference and a digital fingerprint. The verification service checks the presented file against the issuance record and consults the blockchain registry. This helps staff and recipients detect discrepancies and recognize a revoked document.

                    Keep the issued PDF. A copied QR code by itself cannot establish that the text beside it is genuine.
                    MARKDOWN,
                ],
                [
                    'How to cancel or correct a submitted application',
                    'Find the available action for the current stage of review.',
                    false,
                    <<<'MARKDOWN'
                    Open **Applications** and select the request. If **Cancel** is available, enter your reason and confirm. If staff requested a resubmission, open their instructions and correct the specified details.

                    For a mistake on an approved document, send staff the reference and identify the field that needs correction. Keep the issued file intact so staff can check it.
                    MARKDOWN,
                ],
            ]],
            ['Certificates', 'bg-blue-500/20 text-blue-500', [
                [
                    'How to request a certificate of residency',
                    'Prepare a government ID and proof of your San Luis address.',
                    false,
                    <<<'MARKDOWN'
                    1. Open **Services → Certificate of Residency**.
                    2. Select the purpose, such as school enrollment or employment. Add remarks if staff need context.
                    3. Upload a valid government ID and proof of residency, such as a utility bill. Check that the details are readable, then submit.
                    4. Track the request under **Applications**.
                    MARKDOWN,
                ],
                [
                    'How to request a certificate of indigency',
                    'Choose the assistance program your certificate is for.',
                    false,
                    <<<'MARKDOWN'
                    Open **Services → Certificate of Indigency**. Select the purpose, such as scholarship, medical assistance, or financial assistance. Review your details and submit the request.

                    Barangay staff assess eligibility. Check **Applications** and **Notifications** for their decision or a request for more information.
                    MARKDOWN,
                ],
                [
                    'How to ask for a certificate of good moral character',
                    'Contact the barangay about a certificate outside the online service list.',
                    false,
                    <<<'MARKDOWN'
                    Open **Help Center → Open a ticket**. Tell staff you need a *Certificate of Good Moral Character*, which organization requested it, and when it is due. Ask whether Barangay San Luis issues it and what to bring. Wait for their instructions before filing a different certificate request.
                    MARKDOWN,
                ],
                [
                    'How to ask for a first-time job seeker certificate',
                    'Ask staff how to request certification for your first job application.',
                    false,
                    <<<'MARKDOWN'
                    A first-time job seeker certificate has its own requirements. Open a Help Center ticket and explain that you are applying for your first job. Ask which documents to prepare, where to submit them, and whether you need to appear in person. If an employer supplied a checklist, mention the exact certificate name it uses.
                    MARKDOWN,
                ],
                [
                    'How to save your digital certificate',
                    'Open the issued certificate from Documents.',
                    false,
                    <<<'MARKDOWN'
                    After approval, go to **Documents**, find the certificate by type and reference, and choose **Get printable PDF** to use one copy from your allowance. Save the PDF from the viewer. Check the name, purpose, and issue date before giving it to the requesting office.

                    Keep this original file for verification. If you reach the print limit, ask staff about access.
                    MARKDOWN,
                ],
                [
                    'How to correct information on a certificate',
                    'Tell staff exactly which field needs attention.',
                    false,
                    <<<'MARKDOWN'
                    Open **Documents** and find the certificate reference. Send a Help Center ticket with that reference, the field as printed, and the correction you are requesting. Ask what evidence staff need before sending identification. If staff issue a replacement, use the version they confirm is current.
                    MARKDOWN,
                ],
                [
                    'How to check certificate validity and expiration',
                    'Read the dates on the certificate and confirm what the recipient accepts.',
                    false,
                    <<<'MARKDOWN'
                    Look for the issue date and any expiry date on the issued copy. Use the public verification page to check the document’s current status. A school, employer, or other recipient may require a recently issued copy, so ask what issue dates it accepts before submitting yours.
                    MARKDOWN,
                ],
                [
                    'How to check that a digital barangay certificate is genuine',
                    'Give the recipient a way to check the issued copy.',
                    false,
                    <<<'MARKDOWN'
                    Provide the original issued PDF and its permit number or QR reference. The recipient can open the public verification page, supply the reference, and upload the file. Compare the result with the certificate being presented. If the details differ, ask the issuing barangay to review the reference.
                    MARKDOWN,
                ],
            ]],
            ['Verification & QR', 'bg-violet-500/20 text-violet-500', [
                [
                    'How to verify your permit using QR',
                    'Use the QR reference and your copy of the document together.',
                    true,
                    <<<'MARKDOWN'
                    1. Scan the QR code with your phone camera and open the BarangayChain PH verification page.
                    2. Check the reference and upload the permit as a PDF, JPEG, or PNG, up to 10 MB. Only the original issued PDF can be authenticated. Images can be checked for field differences.
                    3. Submit the file and read the result. Compare the displayed permit number, document type, and dates with your copy.
                    MARKDOWN,
                ],
                [
                    'How to verify a document using its reference number',
                    'Use the public form if the QR code is unavailable.',
                    false,
                    <<<'MARKDOWN'
                    Open [Verify a permit](/verify) and enter the permit number printed on the document or its QR reference. Choose the corresponding PDF, JPEG, or PNG file, up to 10 MB, and submit. If the reference is unrecognized, compare each character with the issued copy.
                    MARKDOWN,
                ],
                [
                    'Understanding blockchain-verified documents',
                    'Learn what the portal checks after you upload a file.',
                    true,
                    <<<'MARKDOWN'
                    The verifier looks up the issuance record, checks the uploaded file against the document details, and checks the current blockchain record. Only the original issued PDF can be authenticated. Images can be checked for field differences.

                    Scanning the QR code opens the verification form. Read the result after uploading the document before relying on it.
                    MARKDOWN,
                ],
                [
                    'What the document verification results mean',
                    'Know when to accept a match, retry, or ask staff to investigate.',
                    false,
                    <<<'MARKDOWN'
                    - **Valid document** — the document passed the checks against the current record.
                    - **File could not be authenticated** — check the permit number and upload the original issued PDF. A photo or scan can only be checked for field differences.
                    - **Expired** — the recorded validity period has ended.
                    - **Revoked** — the issuing office has withdrawn this version.
                    - **Cannot verify right now** — blockchain confirmation is temporarily unavailable. Try again later.
                    MARKDOWN,
                ],
                [
                    'What to do if a QR code will not scan',
                    'Try a clearer scan or verify with the printed permit number.',
                    false,
                    <<<'MARKDOWN'
                    Lay the document flat, avoid glare, and keep all four corners of the code in view. If the camera still cannot read it, open [Verify a permit](/verify) and type the printed permit number. Only the original issued PDF can be authenticated. Images can be checked for field differences. If you still need help, tell staff which message appeared.
                    MARKDOWN,
                ],
                [
                    'How to report a suspicious document',
                    'Send a private report with the reference and the discrepancy.',
                    false,
                    <<<'MARKDOWN'
                    First check the reference and file on the public verification page. Note the result and which detail looks wrong, such as a different issue date. Signed-in residents can open a Help Center ticket with those details. Other verifiers can contact Barangay San Luis directly. Share personal details only through the channel staff provide.
                    MARKDOWN,
                ],
            ]],
            ['Account & Identity', 'bg-emerald-500/20 text-emerald-500', [
                [
                    'How to create a BarangayChain PH account',
                    'Register before starting your first service request.',
                    false,
                    <<<'MARKDOWN'
                    Open the registration page and choose your email address or mobile number. Enter your full name, create a password, and confirm it. Follow the prompts to finish registration.

                    After signing in, complete **Identity Verification** before applying for a clearance or certificate. Use the name shown on the ID you will present.
                    MARKDOWN,
                ],
                [
                    'Completing digital identity verification',
                    'Prepare your personal details, ID, and selfie for review.',
                    true,
                    <<<'MARKDOWN'
                    Open **Identity Verification** in your citizen account. Check your personal details, follow the prompts for your ID and selfie, and review the images before submitting. Make sure the ID text is readable and your face is clearly visible.

                    Check the verification status after submission. If staff request another image, read their reason before uploading a replacement.
                    MARKDOWN,
                ],
                [
                    'How to sign in to BarangayChain PH',
                    'Use the same contact method you registered with.',
                    false,
                    <<<'MARKDOWN'
                    On the sign-in page, select the email or mobile number option. Enter your registered contact and password, then complete any verification prompt. Staff using an employee ID can enter it in the **Email Address or Employee ID** field.

                    If you cannot remember your password, use **Forgot Password** on the sign-in page.
                    MARKDOWN,
                ],
                [
                    'How to reset a forgotten password',
                    'Recover access using the contact associated with your account.',
                    false,
                    <<<'MARKDOWN'
                    Choose **Forgot Password** on the sign-in page and use the recovery option for your registered contact. For email recovery, open the reset message and follow its link. For SMS recovery, enter the verification code in the portal. Set a new password, then sign in again. Keep the reset link and code private.
                    MARKDOWN,
                ],
                [
                    'How to update your profile',
                    'Check and correct the personal details on your resident account.',
                    false,
                    <<<'MARKDOWN'
                    Open **Profile** after signing in. With a verified account, update the editable contact or address fields, check the spelling, and save. Reopen the profile to confirm the change.

                    If a verified field is locked, or an issued certificate still contains an old address, ask staff for a correction and include the document reference where relevant.
                    MARKDOWN,
                ],
                [
                    'Who can do what in the portal?',
                    'Find the right person for applications, approvals, and verification.',
                    false,
                    <<<'MARKDOWN'
                    - **Residents** submit and track their own requests, read messages, and access their issued documents.
                    - **Barangay staff** review requests and communicate with applicants according to their assigned permissions.
                    - **Administrators** manage authorized accounts and administrative functions. Approval and identity review depend on the permissions assigned.
                    - **Public verifiers** use the public verification form to check a document without a citizen account.
                    MARKDOWN,
                ],
                [
                    'How the portal handles your personal data',
                    'Use the private channels for identity documents and account requests.',
                    false,
                    <<<'MARKDOWN'
                    Submit ID images through **Identity Verification**. Your citizen account gives you access to your applications and tickets. The public verification result masks the holder’s name while showing details needed to check the permit.

                    For a question about your personal records, use the privacy request option in your profile. Describe the record you want staff to review and avoid including passwords or verification codes.
                    MARKDOWN,
                ],
                [
                    'How to read notifications and reply to messages',
                    'Follow up in the conversation attached to your request.',
                    false,
                    <<<'MARKDOWN'
                    Open **Notifications** and select the update you want to read. For a question about an application, open its ticket so staff can see the request history. For a general question, go to **Help Center → Open a ticket**.

                    To read a reply, return to **Your help tickets**, open the conversation, and reply there while the ticket is open.
                    MARKDOWN,
                ],
                [
                    'How to use the portal on a phone or computer',
                    'Use the same citizen account in your web browser.',
                    false,
                    <<<'MARKDOWN'
                    Open the BarangayChain PH portal in your browser and sign in. On a phone, use the navigation menu to reach **Services**, **Applications**, **Help Center**, or **Documents**. When a form asks for an upload, choose a readable file from your device.

                    Public document verification works from a browser too. Scan the QR code or open the verification form and enter the printed permit number.
                    MARKDOWN,
                ],
            ]],
        ];

        DB::transaction(function () use ($data) {
            foreach ($data as $i => [$name, $color, $articles]) {
                $category = HelpCategory::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'color' => $color, 'sort_order' => $i],
                );

                foreach ($articles as [$title, $summary, $popular, $body]) {
                    $category->articles()->updateOrCreate(
                        ['slug' => Str::slug($title)],
                        ['title' => $title, 'summary' => $summary, 'is_popular' => $popular, 'body' => $body],
                    );
                }
            }
        });
    }
}
