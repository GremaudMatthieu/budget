<?php

namespace App\UserContext\Infrastructure\Adapters;

use App\SharedContext\Domain\Ports\Outbound\UrlGeneratorInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\Ports\Outbound\MailerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface as SymfonyMailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class MailerAdapter implements MailerInterface
{
    public function __construct(
        private SymfonyMailerInterface $mailer,
        private UrlGeneratorInterface $urlGeneratorAdapter,
        private TranslatorInterface $translator,
    ) {
    }

    #[\Override]
    public function sendPasswordResetEmail(UserViewInterface $user, string $token): void
    {
        $this->mailer->send(
            new Email()
                ->from('no-reply@gogobudgeto.com')
                ->to($user->getEmail())
                ->subject($this->translator->trans('password_reset.subject', [], 'messages', $user->languagePreference))
                ->html(
                    $this->generatePasswordResetEmailContent(
                        $user,
                        $this->generatePasswordResetUrl($token),
                        $user->languagePreference,
                    ),
                ),
        );
    }

    private function generatePasswordResetEmailContent(
        UserViewInterface $user,
        string $passwordResetUrl,
        string $locale,
    ): string {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="{$locale}">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$this->translator->trans('password_reset.subject', [], 'messages', $locale)}</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f0f0f0; margin: 0; padding: 0;">
            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                <tr>
                    <td style="padding: 40px 20px; text-align: center; background: linear-gradient(145deg, #f0f0f0, #ffffff);">
                        <img src="https://via.placeholder.com/150x50?text=GoGoBudgeto" alt="GoGoBudgeto Logo" style="max-width: 150px; height: auto;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 20px;">
                        <h1 style="color: #3498db; font-size: 24px; margin-bottom: 20px;">{$this->translator->trans('password_reset.subject', [], 'messages', $locale)}</h1>
                        <p style="margin-bottom: 20px;">{$this->translator->trans('password_reset.greeting', ['%firstname%' => $user->firstname], 'messages', $locale)}</p>
                        <p style="margin-bottom: 20px;">{$this->translator->trans('password_reset.intro', [], 'messages', $locale)}</p>
                        <p style="margin-bottom: 30px;">{$this->translator->trans('password_reset.instruction', [], 'messages', $locale)}</p>
                        <table cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td align="center">
                                    <a href="{$passwordResetUrl}" style="display: inline-block; padding: 12px 24px; background-color: #3498db; color: #ffffff; text-decoration: none; font-weight: bold; border-radius: 5px; transition: background-color 0.3s ease;">{$this->translator->trans('password_reset.button', [], 'messages', $locale)}</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin-top: 30px;">{$this->translator->trans('password_reset.link_text', [], 'messages', $locale)}</p>
                        <p style="margin-bottom: 20px; word-break: break-all;"><a href="{$passwordResetUrl}" style="color: #3498db;">{$passwordResetUrl}</a></p>
                        <p style="margin-bottom: 20px;">{$this->translator->trans('password_reset.expiry', [], 'messages', $locale)}</p>
                        <p style="margin-bottom: 20px;">{$this->translator->trans('password_reset.support', [], 'messages', $locale)}</p>
                        <p>{$this->translator->trans('password_reset.signature', [], 'messages', $locale)}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 20px; text-align: center; background-color: #f8f8f8; font-size: 12px; color: #666;">
                        <p>&copy; 2025 GoGoBudgeto. All rights reserved.</p>
                        <p>If you have any questions, please contact us at <a href="mailto:support@gogobudgeto.com" style="color: #3498db;">support@gogobudgeto.com</a></p>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        HTML;
    }

    private function generatePasswordResetUrl(string $token): string
    {
        return $this->urlGeneratorAdapter->generate(
            'app_user_reset_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        $this->mailer->send($message, $envelope);
    }
}
