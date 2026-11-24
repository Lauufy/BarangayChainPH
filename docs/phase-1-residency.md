# Phase 1 — Certificate of Residency pilot

## Working requirements

These requirements come from the project team's description of Barangay San Luis, Antipolo City. The Certificate of Residency is the proposed pilot because the team supplied a draft certificate.

| Step | Resident or reviewer action | Repository finding |
| --- | --- | --- |
| Verify identity | The resident submits a valid ID. The barangay recommends a Philippine National ID in any accepted form and accepts other valid IDs. | The application has a dedicated citizen identity submission and staff review workflow. Detailed ID handling belongs to Phase 2. |
| Apply for the certificate | The resident completes the application, uploads a fresh copy of a valid ID for this application, and supplies supporting documents for the selected service. | The residency entry in `config/barangay_services.php` lists purpose, optional remarks, a valid government ID, and proof of residency. It gives a utility bill as an example. |
| Review | An admin may approve, request correction, or reject. Staff may do the same while the admin grants review permission. The resident resubmits after a correction request. | The permit route group in `routes/web/admin.php` uses `permits.review` and includes approval and rejection. A dedicated correction action is absent from that group. Verify revocation during an open review when implementing permissions. |
| Issue and release | A barangay official signs the certificate. Offer barangay hall pickup and download of the already signed certificate. Use the supplied certificate as the provisional layout. | The provisional layout includes the issuing barangay, certificate title, date, permit and reference numbers, resident details, purpose, QR verification, and signature area. |

## Proof of residency research — 24 November 2026

An official online checklist for issuance by Barangay San Luis, Antipolo City could not be verified. The application's utility bill example is provisional. Other public services provide examples to discuss with local staff:

- Barangay Magsaysay in Diffun lists a valid ID for its own residency certification. [Barangay Citizen’s Charter](https://diffunquirino.gov.ph/wp-content/uploads/2025/06/Barangay-Magsaysay-Diffun-Citizens-Charter-Handbook.pdf)
- PHLPost accepts a utility bill or a certified true copy of a lease as address evidence for its postal ID service. These are candidate examples for local confirmation. [PHLPost Citizen’s Charter](https://phlpost.gov.ph/wp-content/uploads/2026/04/APPROVED-PHLPost-Citizens-Charter-Series-of-2026.pdf)

## Phase 1 status

The project team confirmed a fresh ID upload per service application, signature by a barangay official, and both physical pickup and signed download. The signing method and final layout remain design details.

Local validation remains open for one requirement: can an ID bearing the resident's current San Luis address establish residency on its own, and which supporting documents does the barangay accept when the ID lacks that address or the resident has no bill in their name? Record the local answer, reviewing role, and date before presenting a definitive checklist to residents.
