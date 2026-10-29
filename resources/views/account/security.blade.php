@extends('layouts.app')
@section('title','Account security | Arewa Civic Tracker')
@section('content')
<div class="account-page">
    <div class="account-heading"><span class="eyebrow">SECURE ACCOUNT ACCESS</span>
      <h1>{{ $mustChange ? 'Set your own password' : 'Account security' }}</h1>
      <p class="muted">{{ $mustChange ? 'Replace your temporary password before accessing your reporting workspace.' : 'Update your account password to protect your access.' }}</p>
    </div>
    <section class="card account-form">
      <form action="{{ route('account.password.update') }}" method="POST">@csrf
      <div class="field"><label for="current-password">Current password</label><input id="current-password" type="password" name="current_password" required autocomplete="current-password"></div>
      <div class="field"><label for="new-password">New password</label><input id="new-password" type="password" name="password" required autocomplete="new-password"></div>
      <div class="field"><label for="password-confirmation">Confirm new password</label><input id="password-confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
      <p data-form-status role="status" hidden></p>
      <button type="submit" class="btn"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Save secure password</button>
      </form>
    </section>
<section class="card account-form two-factor-card">
 <h2><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Two-factor authentication (2FA)</h2>
 <p class="muted">Secure your account using an authenticator app such as Google Authenticator, Microsoft Authenticator or 1Password. An additional six-digit verification code is required at sign-in once enabled.</p>
 @if(auth()->user()->two_factor_confirmed_at)
   <p class="security-enabled"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> 2FA is active on your account.</p>
   <form action="{{ route('two-factor.disable') }}" method="POST">@csrf
     <div class="field"><label>Current password</label><input type="password" name="current_password" autocomplete="current-password" required></div>
     <div class="field"><label>Authenticator code or unused recovery code</label><input name="code" autocomplete="one-time-code" required maxlength="40"></div>
     <button class="btn-outline" type="submit"><i class="fa-solid fa-lock-open" aria-hidden="true"></i> Disable 2FA</button>
   </form>
 @elseif($twoFactorQr)
   <p><strong>Step 1:</strong> Scan this QR code in your authenticator app. Do not share it.</p>
   <img src="{{ $twoFactorQr }}" width="230" height="230" alt="Private authenticator setup QR code" class="two-factor-qr">
   <p><strong>Step 2:</strong> Enter the six-digit code from the app to finish activation. Setup expires in ten minutes.</p>
   <form method="POST" action="{{ route('two-factor.confirm') }}">@csrf
     <div class="field"><label>Six-digit authenticator code</label><input name="code" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required></div>
     <button type="submit" class="btn"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Activate 2FA</button>
   </form>
 @else
   <form method="POST" action="{{ route('two-factor.setup') }}">@csrf
     <div class="field"><label>Confirm current password</label><input name="current_password" type="password" autocomplete="current-password" required></div>
     <button type="submit" class="btn"><i class="fa-solid fa-qrcode" aria-hidden="true"></i> Set up 2FA</button>
   </form>
 @endif
 @if(session('recovery_codes'))
 <div class="recovery-codes"><h3>Save your recovery codes now</h3><p>Each recovery code works only once. Store them securely. They will not be displayed again.</p>
 <pre id="recovery-text">{{ implode("\n",session('recovery_codes')) }}</pre>
 <button type="button" class="btn-outline" onclick="navigator.clipboard.writeText(document.getElementById('recovery-text').innerText).then(()=>this.textContent='Copied').catch(()=>this.textContent='Copy unavailable')"><i class="fa-solid fa-copy" aria-hidden="true"></i> Copy recovery codes</button>
 </div>
 @endif
</section>
</div>
@endsection
