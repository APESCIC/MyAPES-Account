<label for="address_line_1">Address line 1</label>
<input id="address_line_1" name="address_line_1" value="{{ old('address_line_1', $profile?->address_line_1) }}" required>
<label for="address_line_2">Address line 2</label>
<input id="address_line_2" name="address_line_2" value="{{ old('address_line_2', $profile?->address_line_2) }}">
<div class="row">
    <div><label for="town_city">Town or city</label><input id="town_city" name="town_city" value="{{ old('town_city', $profile?->town_city) }}" required></div>
    <div><label for="county">County</label><input id="county" name="county" value="{{ old('county', $profile?->county) }}"></div>
    <div><label for="postcode">Postcode</label><input id="postcode" name="postcode" value="{{ old('postcode', $profile?->postcode) }}" required></div>
</div>
<div class="row">
    <div><label for="mobile_number">Mobile number</label><input id="mobile_number" name="mobile_number" value="{{ old('mobile_number', $profile?->mobile_number) }}" placeholder="+447700900123" required></div>
    <div><label for="landline_number">Landline number</label><input id="landline_number" name="landline_number" value="{{ old('landline_number', $profile?->landline_number) }}"></div>
    <div><label for="whatsapp_number">Separate WhatsApp number</label><input id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $profile?->whatsapp_number) }}"><small>Leave blank to use your mobile number.</small></div>
</div>
<label for="telegram_username">Telegram username</label>
<input id="telegram_username" name="telegram_username" value="{{ old('telegram_username', $profile?->telegram_username) }}">
@if (config('myapes.consent.privacy_notice_url'))
    <p><a href="{{ config('myapes.consent.privacy_notice_url') }}" target="_blank" rel="noopener noreferrer">Read the privacy notice</a></p>
@endif
<fieldset>
    <legend>Your MyAPES services</legend>
    @foreach (['apes-cic' => 'APES CIC', 'shelter-rescue' => 'APES Shelter and Rescue', 'pet-care-clinic' => 'APES Pet Care Clinic'] as $key => $label)
        <label class="inline-check"><input type="checkbox" name="services[]" value="{{ $key }}" @checked(in_array($key, old('services', $selectedServices), true))> {{ $label }}</label>
    @endforeach
</fieldset>
<fieldset>
    <legend>Optional contact preferences</legend>
    @foreach (['calls' => 'Calls', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'telegram' => 'Telegram', 'email' => 'Email'] as $channel => $label)
        <label class="inline-check"><input type="checkbox" name="contact_{{ $channel }}" value="1" @checked(old('contact_'.$channel, $preference?->{$channel} ?? false))> {{ $label }}</label>
    @endforeach
    <input type="hidden" name="contact_preferences_confirmed" value="1">
</fieldset>
