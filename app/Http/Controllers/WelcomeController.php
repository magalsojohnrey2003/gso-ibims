<?php

namespace App\Http\Controllers;

use App\Models\Item;

class WelcomeController extends Controller
{
    /**
     * Render the public landing page.
     */
    public function landing()
    {
        $featuredItems = Item::query()
            ->where('is_borrowable', true)
            ->latest()
            ->take(3)
            ->get();

        return view('landing', [
            'featuredItems' => $featuredItems,
        ]);
    }

    /**
     * Render a read-only version of the Borrow Items page.
     */
    public function publicBorrowItems()
    {
        $items = Item::query()
            ->where('is_borrowable', true)
            ->orderBy('name')
            ->get();

        return view('public.borrow-items', [
            'items' => $items,
        ]);
    }
}
