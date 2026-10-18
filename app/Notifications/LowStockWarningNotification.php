<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\LowStockWarningMail;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;

/**
 * A low stock alert for the admin users.
 *
 * Queued, so the worker delivers the database row and the mail after the order
 * pipeline's transaction has committed.
 */
class LowStockWarningNotification extends Notification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     *
     * @param  Product|array<int, Product>  $products
     */
    public function __construct(
        public Product|array $products,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class, 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): Mailable
    {
        return (new LowStockWarningMail($this->products))->to($notifiable);
    }

    /**
     * The notification in the panel. Building it through Filament's own
     * notification rather than spelling the payload out is what keeps it
     * standing: `getDatabaseMessage()` marks it persistent, so a low stock never
     * slips by with a toast that closes itself, and the acknowledge action is
     * what marks it read.
     */
    public function toDatabase(object $notifiable): array
    {
        $productNames = $this->getProductsNames();

        return FilamentNotification::make()
            ->title('Low Stock Warning')
            ->body("The stock for {$productNames} is running low.")
            ->warning()
            ->actions([
                Action::make('acknowledge')
                    ->label('Acknowledge')
                    ->button()
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }

    public function getProductsNames(): string
    {
        return is_array($this->products)
            ? implode(
                ', ',
                array_map(static fn ($product) => $product->name, $this->products),
            )
            : $this->products->name;
    }
}
