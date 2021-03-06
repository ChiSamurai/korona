<?php

namespace Korona\Repositories;

use Korona\User;

class UserRepository
{
    public function getAll()
    {
        return User::all();
    }

    public function getTrashed()
    {
        return User::onlyTrashed()->get();
    }

    public function getAllWithTrashed()
    {
        return User::withTrashed()->get();
    }

    public function getActive()
    {
        return User::where('active', true)->get();
    }

    public function getSelectData()
    {
        return $this->getAll()->map(function ($item) {
            $item->displayName = $item->login;
            return $item;
        })->pluck('displayName', 'id')->prepend('', '')->all();
    }

    public function getActiveSelectData()
    {
        return $this->getActive()->map(function ($item) {
            $item->displayName = $item->login;
            return $item;
        })->pluck('displayName', 'id')->prepend('', '')->all();
    }
}
