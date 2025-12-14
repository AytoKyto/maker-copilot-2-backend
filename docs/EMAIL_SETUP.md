# Configuration des Emails - Maker Copilot

## Configuration requise

Pour que l'envoi d'emails fonctionne correctement, vous devez configurer les paramètres SMTP dans votre fichier `.env.local`.

## Étapes de configuration

### 1. Créer le fichier .env.local

Copiez le fichier d'exemple :
```bash
cp .env.local.example .env.local
```

### 2. Configurer votre provider email

Choisissez l'un des providers suivants et ajoutez la configuration dans `.env.local` :

#### Gmail
```env
MAILER_DSN=gmail://votre-email@gmail.com:mot-de-passe-application@default
```
**Important** : Utilisez un [mot de passe d'application](https://support.google.com/accounts/answer/185833?hl=fr), pas votre mot de passe habituel.

#### SendGrid
```env
MAILER_DSN=sendgrid://VOTRE_API_KEY@default
```

#### Mailgun
```env
MAILER_DSN=mailgun+smtp://USERNAME:PASSWORD@default?region=us
```

#### SMTP Standard
```env
MAILER_DSN=smtp://user:password@smtp.example.com:587
```

#### Mode développement (emails stockés localement)
```env
MAILER_DSN=native://default
```

### 3. Tester la configuration

Utilisez la commande de test :
```bash
php bin/console app:test-email votre-email@example.com
```

Ou via l'API :
```bash
curl -X POST http://localhost:8000/api/test-email \
  -H "Content-Type: application/json" \
  -d '{"email":"votre-email@example.com"}'
```

## Dépannage

### Erreur "Connection could not be established"
- Vérifiez vos identifiants SMTP
- Assurez-vous que le port est correct (587 pour TLS, 465 pour SSL)
- Vérifiez que votre firewall autorise les connexions sortantes

### Gmail : "Authentication failed"
- Activez la validation en deux étapes
- Créez un mot de passe d'application
- Utilisez ce mot de passe dans MAILER_DSN

### Emails non reçus
- Vérifiez le dossier spam
- Assurez-vous que l'adresse d'envoi est valide
- Vérifiez les logs : `tail -f var/log/dev.log`

## Templates d'email

Les templates d'email sont stockés dans `templates/email/` :
- `welcome.html.twig` : Email de bienvenue après inscription

## Logs

Les logs d'envoi d'email sont disponibles dans :
- Développement : `var/log/dev.log`
- Production : `var/log/prod.log`

Pour suivre les logs en temps réel :
```bash
tail -f var/log/dev.log | grep -i mail
```