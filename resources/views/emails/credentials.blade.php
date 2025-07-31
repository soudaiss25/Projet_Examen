<!DOCTYPE html>
<html>
<head>
    <title>Vos identifiants</title>
</head>
<body>
<p>Bonjour {{ $user->nom }} {{ $user->prenom }},</p>
<p>Voici vos identifiants :</p>
<ul>
    <li>Email : {{ $user->email }}</li>
    <li>Mot de passe : {{ $password }}</li>
</ul>
</body>
</html>
