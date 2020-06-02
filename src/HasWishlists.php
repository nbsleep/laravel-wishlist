<?php

namespace LamaLama\Wishlist;

use DB;
use Exception;

trait HasWishlists
{
    /**
     * Add wish to a wishlist
     * @return void
     */
    public function wish($model = null, $wishlist = null)
    {
        if (! $model) {
            throw new Exception('Model not set');
        }

        if (! $this->wishExists($model, $wishlist)) {
            return $this->createWish($model, $wishlist);
        }
    }

    /**
     * Remove wish from a wishlist
     * @return void
     */
    public function unwish($model = null, $wishlist = null)
    {
        if (! $model) {
            throw new Exception('Model not set');
        }

        $this->deleteWish($model, $wishlist);
    }

    /**
     * Get all wishes for the user
     */
    public function wishes()
    {
        $items = DB::table('wishlist')
            ->where('user_id', $this->id)
            ->get();

        return $this->wishResponse($items);
    }

    /**
     * Get all wishlists for the user
     */
    public function wishlists()
    {
        $items = DB::table('wishlist')
            ->where('user_id', $this->id)
            ->get();

        return $this->wishlistResponse($items);
    }

    /**
     * wishlist
     * @return void
     */
    public function wishlist(string $collectionName = null)
    {
        if (! $collectionName) {
            throw new Exception('Collection name not set');
        }

        $items = DB::table('wishlist')
            ->where('user_id', $this->id)
            ->where('collection_name', $collectionName)
            ->get();

        return $this->wishResponse($items);
    }

    /**
     * wishExists
     * @param  [type] $model
     * @return [type]
     */
    private function wishExists($model)
    {
        return DB::table('wishlist')
            ->where('user_id', $this->id)
            ->where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->where('collection_name', 'default')
            ->first();
    }

    /**
     * createWish
     * @param  [type] $model
     * @param  [type] $collectionName
     * @return [type]
     */
    private function createWish($model, $collectionName)
    {
        if (! $collectionName) {
            $collectionName = config('wishlist.default_list_name');
        }

        DB::table('wishlist')
            ->insert([
                'user_id' => $this->id,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'collection_name' => $collectionName
            ]);
    }

    /**
     * deleteWish
     * @param  [type] $model
     * @param  [type] $collectionName
     * @return [type]
     */
    private function deleteWish($model, $collectionName)
    {
        if (! $collectionName) {
            $collectionName = config('wishlist.default_list_name');
        }

        DB::table('wishlist')
            ->where('user_id', $this->id)
            ->where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->where('collection_name', $collectionName)
            ->delete();
    }

    /**
     * wishResponse
     * @param  [type] $items
     * @return [type]
     */
    private function wishResponse($items)
    {
        foreach ($items as $item) {
            $types[$item->model_type][] = $item->id;
        }

        foreach ($types as $type => $ids) {
            $models[] = $type::whereIn('id', $ids)->get();
        }

        foreach ($models as $model) {
            foreach ($model as $item) {
                $wishes[] = $item;
            }
        }

        return collect($wishes);
    }

    /**
     * wishlistResponse
     * @param  [type] $items
     * @return [type]
     */
    private function wishlistResponse($items)
    {
        foreach ($items as $item) {
            $lists[$item->collection_name][] = $item->id;
        }

        foreach ($lists as $collectionName => $listItems) {
            foreach ($items as $item) {
                $types[$item->model_type][] = $item->id;
            }

            $models = [];
            foreach ($types as $type => $ids) {
                $models[] = $type::whereIn('id', $ids)->get();
            }

            $lists[$collectionName] = $models;
        }

        return collect($lists);
    }
}
