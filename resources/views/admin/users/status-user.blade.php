{{-- Account status popup. Filled by JS from window.userRecords (set in index.blade.php). --}}
<form id="userStatusForm" action="#" method="POST" class="contents">
    @csrf
    @method('PATCH')

    <x-admin.modal id="userStatusModal" title="Update account status"
        subtitle="Choose whether this account is active, inactive, or suspended." size="max-w-lg"
        :close-on-backdrop="false">

        <input type="hidden" name="status_user_id" id="userStatusUserId" value="{{ $statusOld['status_user_id'] ?? '' }}">

        <div class="space-y-5 p-6">
            <x-feedbacks.form-errors id="userStatusErrors" bag="updateUserStatus" />

            <div class="rounded-lg border border-(--gray-200) bg-(--gray-50) px-4 py-3 dark:border-white/10 dark:bg-white/5">
                <p id="userStatusName" class="text-sm font-semibold text-(--gray-900) dark:text-(--off-white)"></p>
                <p id="userStatusEmail" class="mt-1 text-xs text-(--gray-500) dark:text-(--gray-300)"></p>
            </div>

            <x-form.input light id="userStatus_status" name="status" label="Account Status" type="select"
                required :options="$statuses" :use-old="false" :value="$statusOld['status'] ?? null" />
        </div>

        <x-slot:footer>
            <div class="grid grid-cols-2 gap-3 sm:ml-auto sm:w-80">
                <button type="button" data-modal-close
                    class="w-full rounded-md border border-(--gray-300) bg-(--white) px-4 py-2.5 text-sm font-semibold text-(--gray-700) transition hover:bg-(--gray-100) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-(--blue-500) dark:border-white/20 dark:bg-transparent dark:text-(--gray-200) dark:hover:bg-white/10 dark:focus-visible:ring-(--celadon-500)">
                    Cancel
                </button>

                <x-form.button color="#1F3F7A" hoverColor="#173266" type="submit" loading-text="Saving..."
                    class="w-full">
                    Save Status
                </x-form.button>
            </div>
        </x-slot:footer>
    </x-admin.modal>
</form>

<script>
    (function() {
        const modal = document.getElementById('userStatusModal');
        const form = document.getElementById('userStatusForm');
        const $ = (id) => document.getElementById(id);

        window.openUserStatusModal = function(id, keepOld = false) {
            const user = window.userRecords[id];
            if (!user || user.is_self) return;

            if (typeof closeViewUserModal === 'function') closeViewUserModal();
            if (typeof closeEditUserModal === 'function') closeEditUserModal();

            form.action = user.status_url;
            $('userStatusUserId').value = id;
            $('userStatusName').textContent = user.name;
            $('userStatusEmail').textContent = user.email;

            if (!keepOld) {
                $('userStatusErrors')?.remove();
                $('userStatus_status').value = user.status;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        window.closeUserStatusModal = function() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        @if ($statusReopenId)
            openUserStatusModal({{ $statusReopenId }}, true);
        @endif
    })();
</script>
