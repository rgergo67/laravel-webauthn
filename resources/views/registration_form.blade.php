<form method="POST" action="{{ route('webauthn.register') }}" class="form-inline">
    @csrf
    <input type="hidden" name="webauthn_identifier" value="{{ $user->webauthnIdentifier }}" />
    <label for="key_name">{{ trans('webauthn::messages.key_name') }}</label>
    <input type="text" name="key_name" id="key_name" class="form-control" placeholder="{{ trans('webauthn::messages.key_name') }}" />

    <button class="btn btn-success ml-3">Új hozzáadása</button>
</form>
