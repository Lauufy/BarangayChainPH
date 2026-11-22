# File storage

Sensitive images (profile photos, identity photos and selfies) and issued certificate PDFs use the existing Supabase Storage bucket configured by `SUPABASE_S3_BUCKET`. Its credentials and endpoint are provided by `SUPABASE_S3_KEY`, `SUPABASE_S3_SECRET`, `SUPABASE_S3_REGION`, and `SUPABASE_S3_ENDPOINT`. Database columns store relative paths, not public URLs.

Images are displayed using short-lived Supabase signed URLs through `SupabaseStorage`. Profile photos use the signed-photo endpoints. Citizens can access their own identity document, while staff review uses the identity-review document and selfie endpoints. Local identity PDFs are streamed from the private local disk.

Configure the existing Supabase bucket as private in Supabase. Keep public assets such as logos separate from sensitive uploads; do not place sensitive files under `public/storage`.
