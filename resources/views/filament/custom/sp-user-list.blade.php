<div class="space-y-3">
    <table class="min-w-full border border-gray-300 text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-3 py-2 border text-left">Select</th>
                <th class="px-3 py-2 border text-left">Name</th>
                <th class="px-3 py-2 border text-left">Email</th>
            </tr>
        </thead>
        <tbody>
            @foreach(\App\Models\User::take(10)->get() as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 border">
                        <input type="checkbox" name="selected_users[]" value="{{ $user->id }}">
                    </td>
                    <td class="px-3 py-2 border">{{ $user->name }}</td>
                    <td class="px-3 py-2 border">{{ $user->email }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
