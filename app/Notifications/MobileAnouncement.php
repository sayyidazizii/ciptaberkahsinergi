<?php

namespace App\Notifications;

use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Models\CoreAnouncement;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class MobileAnouncement extends Notification
{
    use Queueable;
    public $anouncement;
    /**
     * Create a new notification instance.
     */
    public function __construct(CoreAnouncement $anouncement)
    {
        $this->anouncement = $anouncement;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable): FcmMessage
    {
        return (new FcmMessage(notification:new FcmNotification(
            title: $this->anouncement->title,
            body: Str::limit(strip_tags($this->anouncement->message)),
            image: $this->anouncement->image
            )))
            ->data($this->anouncement->only( 'title', 'message', 'image'))
            ->custom([
                'android' => [
                    'notification' => [
                        'color' => '#0A0A0A',
        		        'channel_id'=> "message"
                    ],
                    'fcm_options' => [
                        'analytics_label' => 'analytics',
                    ],
                ]
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
