{{-- resources/views/admin/users/_form.blade.php --}}
@props(['user' => null, 'action' => '#', 'method' => 'POST'])

<form method="POST" action="{{ $action }}" id="user-form" data-ajax="true">
    @csrf
    @if(strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="input-label">First name</label>
            <input name="first_name" value="{{ old('first_name', $user->first_name ?? '') }}" class="text-input" />
            @error('first_name') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="input-label">Middle name</label>
            <input name="middle_name" value="{{ old('middle_name', $user->middle_name ?? '') }}" class="text-input" />
        </div>
        <div>
            <label class="input-label">Last name</label>
            <input name="last_name" value="{{ old('last_name', $user->last_name ?? '') }}" class="text-input" />
            @error('last_name') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="input-label">Email</label>
            <input name="email" value="{{ old('email', $user->email ?? '') }}" class="text-input" />
            @error('email') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="input-label">Password {{ $user ? '(leave blank to keep current)' : '' }}</label>
            <input type="password" name="password" class="text-input" />
            @error('password') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mt-6">
        <div id="form-errors" class="text-red-600 text-sm mb-3"></div>
        <button type="submit" class="btn btn-primary">{{ $user ? 'Save changes' : 'Create' }}</button>
        <button type="button" class="ml-3 btn btn-secondary" data-action="close-modal">Cancel</button>
    </div>
</form>
