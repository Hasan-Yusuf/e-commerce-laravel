<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\FlashMessage;
use App\Http\Requests\Contact\StoreContactMessageRequest;
use App\Services\Contact\ContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class ContactController extends Controller
{
    public function __construct(
        private ContactService $contactService,
    ) {}

    public function index(): View
    {
        return view('contact.index');
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $this->contactService->store($request->contactMessage());

        return redirect()
            ->route('contact.index')
            ->with(
                FlashMessage::success()
                    ->withTitle(__('contact.form.success'))
                    ->toSession(),
            );
    }
}
