<?php

namespace Gernzy\Server\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'gernzy_carts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_count',
        'items'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'session_id'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'items' => 'array'
    ];

    public static function boot()
    {
        parent::boot();

        // If a cart is being created, force the items
        // array to exist and be empty if it's null
        self::creating(function ($model) {
            if (is_null($model->items)) {
                $model->items = [];
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function addItem(array $item)
    {
        $items = $this->getAllItems();
        $items[$item['product_id']] = $item;
        $this->setAttribute('items', $items);
    }

    public function removeItem(Int $productId)
    {
        $items = $this->getAllItems();
        $item = $items[$productId];
        if ($item) {
            unset($items[$item['product_id']]);
            $this->setAttribute('items', $items);
            return true;
        }

        return false;
    }

    public function updateItemQuantity(Int $productId, Int $quantity)
    {
        $items = $this->getAllItems();
        $item = $items[$productId];
        if ($item) {
            $item['quantity'] = $quantity;
            $items[$item['product_id']] = $item;
            $this->setAttribute('items', $items);
            return true;
        }

        return false;
    }

    private function getAllItems()
    {
        return $this->getAttribute('items') ?? [];
    }

    public function calcCartTotal()
    {
        $items = $this->getAllItems();
        // Get all item ids and pass to the eloquent lookup as array
        $ids = array_column($items, 'product_id');
        $products = Product::find($ids);

        $total = 0;
        foreach ($items as &$item) {
            // Find the product for the current item for the price_cents of the product
            $product = $products->firstWhere('id', '==', $item['product_id']);

            // Workout the cost of the product by its quantity and add the total
            if (isset($product->price_cents)) {
                $total += $product->price_cents * $item['quantity'];
            }
        }

        return $total;
    }
}
