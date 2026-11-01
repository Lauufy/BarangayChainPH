@php
    // Demo data only. Not connected to a database.
    $citizenMessages = [
        [
            'id' => 1,
            'name' => 'Maria Santos',
            'initials' => 'MS',
            'subject' => 'Barangay Clearance',
            'preview' => 'Thank you for your assistance!',
            'time' => '9:42 AM',
            'unread' => true,
            'pending' => true,
            'phone' => '09XX XXX 1234',
            'email' => 'maria@example.com',
            'messages' => [
                ['from' => 'citizen', 'text' => 'Good morning! May I ask about the requirements for a barangay clearance?', 'time' => '9:30 AM'],
                ['from' => 'staff', 'text' => 'Good morning! Please prepare a valid ID and the required supporting documents.', 'time' => '9:35 AM'],
                ['from' => 'citizen', 'text' => 'Thank you for your assistance!', 'time' => '9:42 AM'],
            ],
        ],
        [
            'id' => 2,
            'name' => 'Juan Dela Cruz',
            'initials' => 'JD',
            'subject' => 'Permit Application',
            'preview' => 'May I follow up on my application?',
            'time' => 'Yesterday',
            'unread' => false,
            'pending' => true,
            'phone' => '09XX XXX 5678',
            'email' => 'juan@example.com',
            'messages' => [
                ['from' => 'citizen', 'text' => 'Hello, I would like to follow up on my permit application.', 'time' => 'Yesterday, 2:15 PM'],
                ['from' => 'staff', 'text' => 'Thank you for contacting us. Your inquiry is being reviewed.', 'time' => 'Yesterday, 2:30 PM'],
            ],
        ],
        [
            'id' => 3,
            'name' => 'Ana Reyes',
            'initials' => 'AR',
            'subject' => 'Certificate Request',
            'preview' => 'Thank you! I received the information.',
            'time' => 'Oct 8',
            'unread' => false,
            'pending' => false,
            'phone' => '09XX XXX 9012',
            'email' => 'ana@example.com',
            'messages' => [
                ['from' => 'citizen', 'text' => 'Good afternoon. I would like to inquire about requesting a certificate.', 'time' => 'Oct 8, 10:10 AM'],
                ['from' => 'staff', 'text' => 'Good afternoon! Kindly prepare your valid ID and supporting documents.', 'time' => 'Oct 8, 10:20 AM'],
                ['from' => 'citizen', 'text' => 'Thank you! I received the information.', 'time' => 'Oct 8, 10:25 AM'],
            ],
        ],
    ];
@endphp

@extends('layouts.admin')

@section('title', 'Notifications & Messaging')

@section('content')
    <div class="mb-5">
        <h2 class="text-2xl font-bold text-slate-900">
            Notifications & Messaging
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Manage announcements and communicate with citizens.
        </p>
    </div>

    {{-- Compact notifications banner --}}
    <div
        class="mb-5 flex flex-col gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900">Notifications & Announcements</h3>
                <p class="text-xs text-slate-500">No published announcements yet.</p>
            </div>
        </div>

        <button type="button" id="new-notification"
            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            + New Notification
        </button>
    </div>

    {{-- Messaging dashboard --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <div>
                <h3 class="font-bold text-slate-900">Citizen Messages</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Manage inquiries received by barangay staff.
                </p>
            </div>

            <span
                class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                <span id="unread-count">1 unread</span>
            </span>
        </div>

        <div class="grid min-h-150 grid-cols-1 md:grid-cols-3">

            {{-- Inbox sidebar --}}
            <aside class="border-b border-slate-200 md:col-span-1 md:border-b-0 md:border-r">

                <div class="p-4">
                    <label for="message-search" class="sr-only">Search conversations</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-3 h-4 w-4 text-slate-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7" stroke-width="2" />
                            <path stroke-linecap="round" stroke-width="2" d="m16 16 4 4" />
                        </svg>
                        <input id="message-search" type="search" placeholder="Search citizens..."
                            class="w-full rounded-lg border border-slate-200 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div class="mt-4 flex gap-1 rounded-lg bg-slate-100 p-1">
                        <button type="button" data-filter="all"
                            class="filter-btn flex-1 rounded-md bg-white px-2 py-2 text-xs font-semibold text-blue-700 shadow-sm">
                            All
                        </button>
                        <button type="button" data-filter="unread"
                            class="filter-btn flex-1 rounded-md px-2 py-2 text-xs font-medium text-slate-500 hover:text-slate-900">
                            Unread
                        </button>
                        <button type="button" data-filter="pending"
                            class="filter-btn flex-1 rounded-md px-2 py-2 text-xs font-medium text-slate-500 hover:text-slate-900">
                            Pending
                        </button>
                    </div>
                </div>

                <div id="inbox-list" class="divide-y divide-slate-100">
                    @foreach ($citizenMessages as $index => $citizen)
                        <button type="button" data-id="{{ $citizen['id'] }}"
                            class="conversation-item flex w-full items-start gap-3 border-l-4 p-4 text-left transition
                        {{ $index === 0 ? 'active-conversation border-l-blue-600 bg-blue-50' : 'border-l-transparent hover:bg-slate-50' }}">

                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                                {{ $citizen['initials'] }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="citizen-name truncate text-sm font-semibold text-slate-900">
                                        {{ $citizen['name'] }}
                                    </span>
                                    <span class="shrink-0 text-xs text-slate-400">
                                        {{ $citizen['time'] }}
                                    </span>
                                </div>

                                <p class="mt-1 text-xs font-medium text-slate-600">
                                    {{ $citizen['subject'] }}
                                </p>

                                <p class="mt-1 truncate text-xs text-slate-400">
                                    {{ $citizen['preview'] }}
                                </p>

                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @if ($citizen['unread'])
                                        <span
                                            class="unread-badge rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700">
                                            Unread
                                        </span>
                                    @endif

                                    <span
                                        class="pending-badge rounded-full px-2 py-0.5 text-[10px] font-semibold
                                    {{ $citizen['pending'] ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                        {{ $citizen['pending'] ? 'Pending' : 'Resolved' }}
                                    </span>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>

                <p id="empty-inbox" class="hidden p-6 text-center text-sm text-slate-400">
                    No conversations match this filter.
                </p>
            </aside>

            {{-- Chat panel --}}
            <section class="flex min-w-0 flex-col md:col-span-2">

                {{-- Chat header --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <div id="chat-avatar"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                            MS
                        </div>
                        <div class="min-w-0">
                            <h4 id="chat-name" class="truncate font-semibold text-slate-900">
                                Maria Santos
                            </h4>
                            <p id="chat-subject" class="mt-1 truncate text-xs text-slate-500">
                                Barangay Clearance
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="citizen-details"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            View Details
                        </button>
                        <button type="button" id="resolve-chat"
                            class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">
                            Mark Resolved
                        </button>
                    </div>
                </div>

                {{-- Message timeline --}}
                <div id="chat-timeline" class="flex flex-1 flex-col gap-4 bg-slate-50 p-4 sm:p-6">

                    <div class="date-divider flex items-center gap-3">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <span class="text-[11px] font-medium text-slate-400">Today</span>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>

                    <div class="flex items-start gap-2">
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">
                            MS
                        </div>
                        <div class="max-w-[85%]">
                            <p class="mb-1 text-xs text-slate-400">Maria Santos · 9:30 AM</p>
                            <div
                                class="rounded-2xl rounded-tl-sm border border-slate-200 bg-white p-3 text-sm leading-6 text-slate-700 shadow-sm">
                                Good morning! May I ask about the requirements for a barangay clearance?
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start justify-end gap-2">
                        <div class="max-w-[85%]">
                            <p class="mb-1 text-right text-xs text-slate-400">Barangay Staff · 9:35 AM</p>
                            <div class="rounded-2xl rounded-tr-sm bg-blue-600 p-3 text-sm leading-6 text-white shadow-sm">
                                Good morning! Please prepare a valid ID and the required supporting documents.
                            </div>
                        </div>
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-600">
                            BS
                        </div>
                    </div>

                    <div class="flex items-start gap-2">
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">
                            MS
                        </div>
                        <div class="max-w-[85%]">
                            <p class="mb-1 text-xs text-slate-400">Maria Santos · 9:42 AM</p>
                            <div
                                class="rounded-2xl rounded-tl-sm border border-slate-200 bg-white p-3 text-sm leading-6 text-slate-700 shadow-sm">
                                Thank you for your assistance!
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Reply composer --}}
                <form id="reply-form" class="border-t border-slate-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <label for="reply-message" class="text-sm font-semibold text-slate-700">
                            Reply to citizen
                        </label>

                        <label for="attachment"
                            class="flex cursor-pointer items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="m21.4 11.6-8.5 8.5a5.5 5.5 0 01-7.8-7.8l9.2-9.2a3.5 3.5 0 015 5l-9.2 9.2a1.5 1.5 0 01-2.2-2.2l8.5-8.5" />
                            </svg>
                            Attach File
                        </label>
                        <input id="attachment" type="file" class="hidden">
                    </div>

                    <p id="attachment-name" class="mb-2 hidden break-all text-xs text-blue-600"></p>

                    <div class="flex items-end gap-2">
                        <textarea id="reply-message" rows="2" placeholder="Write your reply..."
                            class="min-w-0 flex-1 resize-none rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></textarea>

                        <button type="submit"
                            class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                            Send
                        </button>
                    </div>

                    <p id="reply-feedback" class="mt-2 hidden text-xs text-amber-600"></p>
                    <p class="mt-2 text-xs text-slate-400">
                        UI preview only. Messages and attachments are not delivered or saved.
                    </p>
                </form>

            </section>
        </div>
    </div>

    {{-- Citizen details modal --}}
    <div id="details-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-900">Citizen Details</h3>
                <button type="button" id="close-details"
                    class="rounded-lg px-3 py-1 text-xl text-slate-400 hover:bg-slate-100"
                    aria-label="Close">&times;</button>
            </div>

            <div class="mt-5 flex items-center gap-3">
                <div id="details-avatar"
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">
                    MS
                </div>
                <div>
                    <p id="details-name" class="font-semibold text-slate-900">Maria Santos</p>
                    <p class="text-xs text-slate-500">Registered citizen · Demo data</p>
                </div>
            </div>

            <div class="mt-5 space-y-3 rounded-xl bg-slate-50 p-4 text-sm">
                <div>
                    <p class="text-xs text-slate-400">Inquiry</p>
                    <p id="details-subject" class="mt-1 font-medium text-slate-700"></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400">Contact number</p>
                    <p id="details-phone" class="mt-1 text-slate-700"></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400">Email</p>
                    <p id="details-email" class="mt-1 text-slate-700"></p>
                </div>
            </div>

            <button type="button" id="close-details-bottom"
                class="mt-5 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                Close
            </button>
        </div>
    </div>

    {{-- New Notification modal --}}
    <div id="notification-modal" class="fixed inset-0 z-60 hidden overflow-y-auto bg-slate-900/50 p-4" role="dialog"
        aria-modal="true" aria-labelledby="notification-modal-title">
        <div class="flex min-h-full items-center justify-center">
            <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">

                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                    <div>
                        <h3 id="notification-modal-title" class="text-xl font-bold text-slate-900">
                            Create New Notification
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Prepare an announcement for Barangay San Luis citizens.
                        </p>
                    </div>
                    <button type="button" id="close-notification-modal"
                        class="rounded-lg px-3 py-1 text-2xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Close notification modal">&times;</button>
                </div>

                <form id="notification-form" class="space-y-5 p-6">
                    <div>
                        <label for="notification-title" class="mb-2 block text-sm font-semibold text-slate-700">
                            Notification Title
                        </label>
                        <input id="notification-title" name="notification_title" type="text" maxlength="120" required
                            placeholder="e.g. Barangay Hall Schedule Update"
                            class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div>
                        <label for="notification-message" class="mb-2 block text-sm font-semibold text-slate-700">
                            Announcement Message
                        </label>
                        <textarea id="notification-message" name="notification_message" rows="5" required
                            placeholder="Write the details of your announcement here..."
                            class="w-full resize-y rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></textarea>
                    </div>

                    <div>
                        <label for="notification-recipients" class="mb-2 block text-sm font-semibold text-slate-700">
                            Recipients
                        </label>
                        <select id="notification-recipients" name="notification_recipients"
                            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            <option value="all">All Citizens</option>
                            <option value="registered">Registered Citizens</option>
                        </select>
                    </div>

                    <div id="notification-preview" class="hidden rounded-xl border border-blue-100 bg-blue-50 p-4"
                        aria-live="polite">
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-blue-700">Preview</p>
                    </div>

                    <p class="rounded-lg bg-amber-50 p-3 text-xs leading-5 text-amber-700">
                        UI prototype only. This preview is not saved to a database and will not be sent to citizens.
                    </p>

                    <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-5">
                        <button type="button" id="cancel-notification"
                            class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Preview Notification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Frontend-only demo interactions --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const citizens = @json($citizenMessages);
            const items = [...document.querySelectorAll('.conversation-item')];

            let activeId = 1;
            let activeFilter = 'all';

            const search = document.getElementById('message-search');
            const inbox = document.getElementById('inbox-list');
            const emptyInbox = document.getElementById('empty-inbox');
            const timeline = document.getElementById('chat-timeline');
            const unreadCount = document.getElementById('unread-count');
            const resolveButton = document.getElementById('resolve-chat');
            const replyForm = document.getElementById('reply-form');
            const replyInput = document.getElementById('reply-message');
            const feedback = document.getElementById('reply-feedback');
            const attachment = document.getElementById('attachment');
            const attachmentName = document.getElementById('attachment-name');

            const modal = document.getElementById('details-modal');

            function activeCitizen() {
                return citizens.find(c => c.id === activeId);
            }

            function updateUnreadCount() {
                const count = citizens.filter(c => c.unread).length;
                unreadCount.textContent = count + ' unread';
            }

            function renderInbox() {
                let visibleCount = 0;

                items.forEach(item => {
                    const citizen = citizens.find(c => c.id === Number(item.dataset.id));
                    const query = search.value.trim().toLowerCase();

                    const matchesSearch =
                        citizen.name.toLowerCase().includes(query) ||
                        citizen.subject.toLowerCase().includes(query);

                    const matchesFilter =
                        activeFilter === 'all' ||
                        (activeFilter === 'unread' && citizen.unread) ||
                        (activeFilter === 'pending' && citizen.pending);

                    const visible = matchesSearch && matchesFilter;

                    item.classList.toggle('hidden', !visible);

                    if (visible) visibleCount++;

                    item.classList.toggle('active-conversation', citizen.id === activeId);
                    item.classList.toggle('bg-blue-50', citizen.id === activeId);
                    item.classList.toggle('border-l-blue-600', citizen.id === activeId);
                    item.classList.toggle('border-l-transparent', citizen.id !== activeId);

                    const unreadBadge = item.querySelector('.unread-badge');
                    if (unreadBadge) unreadBadge.classList.toggle('hidden', !citizen.unread);

                    const status = item.querySelector('.pending-badge');
                    status.textContent = citizen.pending ? 'Pending' : 'Resolved';
                    status.className = 'pending-badge rounded-full px-2 py-0.5 text-[10px] font-semibold ' +
                        (citizen.pending ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700');
                });

                emptyInbox.classList.toggle('hidden', visibleCount > 0);
                updateUnreadCount();
            }

            function addBubble(message, citizen) {
                const isStaff = message.from === 'staff';
                const row = document.createElement('div');
                row.className = 'flex items-start gap-2 ' + (isStaff ? 'justify-end' : '');

                const avatar = document.createElement('div');
                avatar.className =
                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ' +
                    (isStaff ? 'bg-slate-200 text-slate-600' : 'bg-blue-100 text-blue-700');
                avatar.textContent = isStaff ? 'BS' : citizen.initials;

                const wrapper = document.createElement('div');
                wrapper.className = 'max-w-[85%]';

                const meta = document.createElement('p');
                meta.className = 'mb-1 text-xs text-slate-400 ' + (isStaff ? 'text-right' : '');
                meta.textContent = (isStaff ? 'Barangay Staff' : citizen.name) + ' · ' + message.time;

                const bubble = document.createElement('div');
                bubble.className = 'rounded-2xl p-3 text-sm leading-6 shadow-sm ' +
                    (isStaff ?
                        'rounded-tr-sm bg-blue-600 text-white' :
                        'rounded-tl-sm border border-slate-200 bg-white text-slate-700');

                bubble.textContent = message.text;

                wrapper.append(meta, bubble);

                if (isStaff) {
                    row.append(wrapper, avatar);
                } else {
                    row.append(avatar, wrapper);
                }

                timeline.appendChild(row);
            }

            function selectCitizen(id) {
                activeId = id;
                const citizen = activeCitizen();

                citizen.unread = false;

                document.getElementById('chat-name').textContent = citizen.name;
                document.getElementById('chat-avatar').textContent = citizen.initials;
                document.getElementById('chat-subject').textContent = citizen.subject;

                timeline.replaceChildren();

                const dividerRow = document.createElement('div');
                dividerRow.className = 'date-divider flex items-center gap-3';

                const line1 = document.createElement('div');
                line1.className = 'h-px flex-1 bg-slate-200';

                const date = document.createElement('span');
                date.className = 'text-[11px] font-medium text-slate-400';
                date.textContent = 'Conversation history';

                const line2 = line1.cloneNode();

                dividerRow.append(line1, date, line2);
                timeline.appendChild(dividerRow);

                citizen.messages.forEach(message => addBubble(message, citizen));

                resolveButton.textContent = citizen.pending ? 'Mark Resolved' : 'Reopen Conversation';
                resolveButton.className = 'rounded-lg border px-3 py-2 text-xs font-semibold ' +
                    (citizen.pending ?
                        'border-emerald-200 text-emerald-700 hover:bg-emerald-50' :
                        'border-amber-200 text-amber-700 hover:bg-amber-50');

                feedback.classList.add('hidden');
                replyInput.value = '';
                attachment.value = '';
                attachmentName.classList.add('hidden');

                renderInbox();
            }

            document.querySelectorAll('.filter-btn').forEach(button => {
                button.addEventListener('click', function() {
                    activeFilter = this.dataset.filter;

                    document.querySelectorAll('.filter-btn').forEach(btn => {
                        btn.className =
                            'filter-btn flex-1 rounded-md px-2 py-2 text-xs font-medium text-slate-500 hover:text-slate-900';
                    });

                    this.className =
                        'filter-btn flex-1 rounded-md bg-white px-2 py-2 text-xs font-semibold text-blue-700 shadow-sm';

                    renderInbox();
                });
            });

            items.forEach(item => {
                item.addEventListener('click', function() {
                    selectCitizen(Number(this.dataset.id));
                });
            });

            search.addEventListener('input', renderInbox);

            resolveButton.addEventListener('click', function() {
                const citizen = activeCitizen();
                citizen.pending = !citizen.pending;

                feedback.textContent = citizen.pending ?
                    'Demo: Conversation reopened. This change is not saved.' :
                    'Demo: Conversation marked as resolved. This change is not saved.';
                feedback.classList.remove('hidden');

                renderInbox();
                selectCitizen(activeId);
            });

            document.getElementById('citizen-details').addEventListener('click', function() {
                const citizen = activeCitizen();

                document.getElementById('details-name').textContent = citizen.name;
                document.getElementById('details-avatar').textContent = citizen.initials;
                document.getElementById('details-subject').textContent = citizen.subject;
                document.getElementById('details-phone').textContent = citizen.phone;
                document.getElementById('details-email').textContent = citizen.email;

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            document.getElementById('close-details').addEventListener('click', closeModal);
            document.getElementById('close-details-bottom').addEventListener('click', closeModal);

            modal.addEventListener('click', function(event) {
                if (event.target === modal) closeModal();
            });

            attachment.addEventListener('change', function() {
                if (this.files.length) {
                    attachmentName.textContent = 'Selected file: ' + this.files[0].name;
                    attachmentName.classList.remove('hidden');
                } else {
                    attachmentName.classList.add('hidden');
                }
            });

            replyForm.addEventListener('submit', function(event) {
                event.preventDefault();

                const text = replyInput.value.trim();

                if (!text) {
                    feedback.textContent = 'Please type a reply first.';
                    feedback.classList.remove('hidden');
                    return;
                }

                const citizen = activeCitizen();

                citizen.messages.push({
                    from: 'staff',
                    text: text,
                    time: new Date().toLocaleTimeString([], {
                        hour: 'numeric',
                        minute: '2-digit'
                    })
                });

                citizen.preview = text;
                citizen.time = 'Just now';
                citizen.pending = true;

                const dateDivider = timeline.querySelector('.date-divider');
                if (dateDivider) {
                    addBubble(citizen.messages[citizen.messages.length - 1], citizen);
                }

                replyInput.value = '';
                feedback.textContent =
                    'Demo reply added to this preview only. It was not delivered or saved.';
                feedback.classList.remove('hidden');

                renderInbox();
            });

            // New Notification modal (frontend demo only)
            const notificationModal = document.getElementById('notification-modal');
            const notificationForm = document.getElementById('notification-form');
            const notificationPreview = document.getElementById('notification-preview');

            function openNotificationModal() {
                notificationModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                document.getElementById('notification-title').focus();
            }

            function closeNotificationModal() {
                notificationModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            document.getElementById('new-notification').addEventListener('click', openNotificationModal);
            document.getElementById('close-notification-modal').addEventListener('click', closeNotificationModal);
            document.getElementById('cancel-notification').addEventListener('click', closeNotificationModal);

            notificationModal.addEventListener('click', function(event) {
                if (event.target === notificationModal) closeNotificationModal();
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && !notificationModal.classList.contains('hidden')) {
                    closeNotificationModal();
                }
            });

            notificationForm.addEventListener('submit', function(event) {
                event.preventDefault();

                const title = document.getElementById('notification-title').value.trim();
                const message = document.getElementById('notification-message').value.trim();
                const recipients = document.getElementById('notification-recipients');
                const recipientLabel = recipients.options[recipients.selectedIndex].text;

                if (!title || !message) return;

                notificationPreview.innerHTML = '';
                const previewTitle = document.createElement('p');
                previewTitle.className = 'font-semibold text-slate-900';
                previewTitle.textContent = title;

                const previewMessage = document.createElement('p');
                previewMessage.className = 'mt-1 whitespace-pre-wrap text-sm text-slate-600';
                previewMessage.textContent = message;

                const previewRecipients = document.createElement('p');
                previewRecipients.className = 'mt-2 text-xs font-medium text-blue-700';
                previewRecipients.textContent = 'Recipients: ' + recipientLabel;

                notificationPreview.append(previewTitle, previewMessage, previewRecipients);
                notificationPreview.classList.remove('hidden');
            });

            renderInbox();
        });
    </script>

@endsection
