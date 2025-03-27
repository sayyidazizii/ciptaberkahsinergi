<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class MobileNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
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

    public function toFcm($notifiable, $data): FcmMessage
    {
        Log::info('notifiable',$notifiable);
        Log::info('notification',$data);
        if(is_null($notifiable)){
        //    $notifiable = new FcmNotification(
        //         title: 'Account Activated',
        //         body: 'Your account has been activated.',
        //         image: 'https://picsum.photos/200/300'
        //    );
        }
        return (new FcmMessage(notification:new FcmNotification(
            title: 'Account Activated',
            body: 'Your account has been activated.',
            image: 'https://picsum.photos/200/300'
            )))
            ->data($data)
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
