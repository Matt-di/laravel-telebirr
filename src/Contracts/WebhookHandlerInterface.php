<?php

namespace Telebirr\LaravelTelebirr\Contracts;

use Illuminate\Http\Request;

interface WebhookHandlerInterface
{
    /**
     * Handle an incoming webhook from Telebirr.
     *
     * @param Request $request
     * @return mixed
     */
    public function handle(Request $request);
}
