<?php

declare(strict_types=1);

namespace App\Mail;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  Product|array<int, Product>  $products
     */
    public function __construct(
        public Product|array $products,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $productNames = $this->getProductsNames();

        return new Envelope(
            subject: 'Low Stock Alert: Product '.$productNames,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.low-stock-warning',
            with: [
                'products' => $this->products,
                'url' => ProductResource::getUrl('index'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
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
