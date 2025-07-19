{{-- resources/views/credentials.blade.php --}}

@component('mail::message')
# Bonjour {{ $user->prenom }},

Voici vos identifiants de connexion :

- **Email** : {{ $user->email }}
- **Mot de passe** : {{ $password }}

Merci de changer votre mot de passe après la première connexion.

@component('mail::button', ['url' => config('app.url')])
Se connecter
@endcomponent

Merci,<br>
{{ config('app.name') }}
@endcomponent
