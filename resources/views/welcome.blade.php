<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>{{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body {
                width: 100% !important;
            }

            .footer {
                width: 100% !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
    </style>
    {!! $head ?? '' !!}
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center">
            <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td class="header" style="padding: 25px 0; text-align: center;">
                        <a href="{{ config('app.url') }}" style="font-size:19px; font-weight:bold; color:#3d4852; text-decoration:none;">
                            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }} Logo" class="logo" style="height:75px;width:75px;">
                        </a>
                    </td>
                </tr>

            <!-- Email Body -->
                <tr>
                    <td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
                        <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0"
                               role="presentation">
                            <!-- Body content -->
                            <tr>
                                <td class="content-cell">
                                    @component('mail::message')
                                        # 🔒 Réinitialisation de mot de passe

                                        Bonjour,

                                        Vous recevez cet email car nous avons reçu une demande de **réinitialisation de mot de passe** pour votre compte.
                                        <table class="action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td align="center">
                                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                                                        <tr>
                                                            <td>
                                                                <a href=""
                                                                   class="button button-red"
                                                                   target="_blank"
                                                                   style="
                                                                       display:inline-block;
                                                                       background-color:#2d3748;
                                                                       color:#ffffff !important;
                                                                       font-weight:bold;
                                                                       text-decoration:none;
                                                                       border-radius:4px;
                                                                       padding:12px 24px;
                                                                       text-align:center;
                                                                       mso-padding-alt:0;
                                                                       "
                                                                >
                                                                    Réinitialiser mon mot de passe
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                        Ce lien est valide pendant **60 minutes**.
                                        Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer ce message.

                                        Merci,<br>
                                        L’équipe **{{ config('app.name') }}**

                                    <table class="subcopy" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top: 20px; border-top: 1px solid #eaeaea;">
                                        <tr>
                                            <td style="padding-top: 15px; font-size: 13px; color: #6c757d; line-height: 1.5; text-align: center;">
                                                Si vous avez des difficultés à cliquer sur le bouton, copiez et collez l’URL ci-dessous dans votre navigateur :
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td>
                        <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                                <td class="content-cell" align="center" style="padding: 20px 0; color:#b0adc5; font-size:12px;">
                                    &copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
