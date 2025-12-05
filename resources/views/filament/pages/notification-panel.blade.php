<x-filament::page>
    <div class="grid grid-cols-2 gap-6">
        {{-- LEFT SIDE - User List --}}
        <div class="bg-white rounded-xl shadow p-4 border">
            <h2 class="text-lg font-semibold mb-3">SP User List</h2>
            <div class="overflow-y-auto max-h-[500px] border rounded mt-2">
                <table class="min-w-full w-full border-collapse text-sm table-auto">
                    <thead class="bg-gray-100 sticky top-0 text-gray-700 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="p-3 border w-[5%] text-center">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th class="p-3 border w-[35%] text-left">User Details</th>
                            <th class="p-3 border w-[35%] text-left">Job Role</th>
                            <th class="p-3 border w-[25%] text-left">Verification</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (\App\Models\SPUser::limit(30)->get() as $user)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="p-2 border text-center">
                                    <input type="checkbox" name="selected_users[]" value="{{ $user->id }}">
                                </td>
                                <td class="p-3 border align-top">
                                    <div class="font-semibold text-gray-900 text-sm leading-tight">
                                        {{ $user->first_name }} {{ $user->last_name }}
                                    </div>
                                    <div class="text-xs text-gray-600">{{ $user->email }}</div>
                                    <div class="text-xs text-gray-600">{{ $user->mobile1_number }}</div>
                                </td>
                                <td class="p-3 border align-top">
                                    <div class="text-sm text-gray-800">
                                        {{ \App\Filament\Admin\Resources\NotificationPanelResource\Pages\CreateNotificationPanel::getRoleNames($user->intrested_role) }}
                                    </div>
                                </td>
                                <td class="p-3 border align-top">
                                    @if ($user->is_verified === 1)
                                        <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">
                                            Verified
                                        </span>
                                    @elseif($user->is_verified === 2)
                                        <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">
                                            Rejected
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold text-yellow-700 bg-yellow-100 rounded-full">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- RIGHT SIDE - Notification Send Form --}}
        <div class="bg-white rounded-xl shadow p-4 border">
            <h2 class="text-lg font-semibold mb-3">Send Notification</h2>
            <form id="notificationForm">
                @csrf
                <div class="space-y-4">

                    {{-- Subject --}}
                    <div class="mt-4">
                        <label class="block font-medium mb-1">Subject / Title</label>
                        <input type="text" name="title" id="title"
                               class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Enter subject" required>
                    </div>

                    {{-- Type --}}
                    <div>
                        <label class="block font-medium mb-1">Notification Type</label>
                        <select name="type" id="notificationType"
                                class="w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Select Type</option>
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="in-app">In-App</option>
                            <option value="whatsapp">WhatsApp</option>
                        </select>
                    </div>

                    {{-- Template --}}
                    <div>
                        <label class="block font-medium mb-1">Template</label>
                        <select name="template_id" id="templateDropdown"
                                class="w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Select Template</option>
                        </select>
                    </div>

                    {{-- Message --}}
                    <textarea name="message_body" id="hiddenMessageBody" hidden></textarea>

                    <div>
                        <label class="block font-medium mb-1">Message Preview</label>
                        <div id="messageBody"
                             class="w-full border border-gray-300 rounded-md shadow-sm bg-gray-50 p-4 min-h-[150px] prose leading-relaxed"
                             style="white-space: pre-wrap;"></div>
                    </div>

                    {{-- Delivery --}}
                    <div>
                        <label class="block font-medium mb-1">Delivery Option</label>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center space-x-2">
                                <input type="radio" name="delivery_option" value="now" checked>
                                <span class="mr-2">Send Now</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="radio" name="delivery_option" value="schedule">
                                <span class="mr-2">Schedule</span>
                            </label>
                        </div>
                    </div>

                    {{-- Schedule datetime --}}
                    <div id="scheduleContainer" class="hidden">
                        <label class="block font-medium mb-1 mt-2">Select Date & Time</label>
                        <input type="datetime-local" name="scheduled_at" id="scheduledAt"
                               class="w-full border-gray-300 rounded-md shadow-sm">
                    </div>

                    <input type="hidden" name="selected_users" id="selectedUsersInput">


                    <div class="flex justify-end pt-2">
                <button type="submit"
                    class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 transition duration-150">
                    Send Notification
                </button>
            </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 🌐 Full-screen Loader -->
    <div id="global-loader"
         class="fixed inset-0 flex items-center justify-center bg-white/60 backdrop-blur-sm z-[9999] hidden">
        <div class="flex flex-col items-center space-y-3">
            <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            <p class="text-gray-800 font-medium text-sm">Loading, please wait...</p>
        </div>
    </div>

    {{-- JS --}}
    <script>
        const loader = document.getElementById('global-loader');
        const typeDropdown = document.getElementById('notificationType');
        const templateDropdown = document.getElementById('templateDropdown');
        const messageBody = document.getElementById('messageBody');
        const hiddenMessageBody = document.getElementById('hiddenMessageBody');
        const selectedUsersInput = document.getElementById('selectedUsersInput');
        const form = document.getElementById('notificationForm');
        const scheduleContainer = document.getElementById('scheduleContainer');
        const deliveryRadios = document.querySelectorAll('input[name="delivery_option"]');

        // Toggle schedule date-time visibility
        deliveryRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (e.target.value === 'schedule') {
                    scheduleContainer.classList.remove('hidden');
                } else {
                    scheduleContainer.classList.add('hidden');
                }
            });
        });

        // Global loader setup
        (function() {
            const originalFetch = window.fetch;
            window.fetch = async (...args) => {
                loader.classList.remove('hidden');
                try {
                    const response = await originalFetch(...args);
                    return response;
                } finally {
                    setTimeout(() => loader.classList.add('hidden'), 400);
                }
            };
        })();

        // Fetch templates by notification type
        typeDropdown.addEventListener('change', async (e) => {
            const type = e.target.value;
            templateDropdown.innerHTML = '<option value="">Select Template</option>';
            messageBody.innerHTML = '';
            hiddenMessageBody.value = '';

            if (!type) return;

            try {
                const response = await fetch(`/get-templates/${type}`);
                const templates = await response.json();
                templates.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.title;
                    templateDropdown.appendChild(opt);
                });
            } catch (err) {
                console.error('Error fetching templates:', err);
            }
        });

        // Fetch message body when template changes
        templateDropdown.addEventListener('change', async (e) => {
            const templateId = e.target.value;
            messageBody.innerHTML = '';
            hiddenMessageBody.value = '';

            if (!templateId) return;

            try {
                const response = await fetch(`/get-template/${templateId}`);
                const data = await response.json();
                messageBody.innerHTML = data.message_body || '';
                hiddenMessageBody.value = data.message_body || '';
            } catch (err) {
                console.error('Error fetching message body:', err);
            }
        });

        // Select all toggle
        document.getElementById('selectAll')?.addEventListener('change', (e) => {
            document.querySelectorAll('input[name="selected_users[]"]').forEach(chk => chk.checked = e.target.checked);
        });

        // Submit form
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const selectedUsers = Array.from(document.querySelectorAll('input[name="selected_users[]"]:checked'))
                .map(chk => chk.value);

            if (selectedUsers.length === 0) {
                alert('Please select at least one user.');
                return;
            }

            const formData = new FormData(form);
            formData.append('is_scheduled', document.querySelector('input[name="delivery_option"]:checked').value === 'schedule' ? 1 : 0);
            formData.append('selected_users', JSON.stringify(selectedUsers));

            try {
                const response = await fetch("{{ route('notifications.send') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": form.querySelector('input[name="_token"]').value,
                        "Accept": "application/json",
                    },
                    body: formData
                });

                const result = await response.json();
                alert(result.message || (response.ok ? 'Notification processed successfully!' : 'Failed to send.'));
            } catch (error) {
                console.error('Error:', error);
                alert('Something went wrong. Please try again.');
            }
        });
    </script>
</x-filament::page>
