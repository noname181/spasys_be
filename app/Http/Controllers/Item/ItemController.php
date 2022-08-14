<?php

namespace App\Http\Controllers\Item;

use App\Http\Controllers\Controller;
use App\Http\Requests\Item\ItemRequest;
use App\Http\Requests\Item\ItemSearchRequest;
use App\Models\Item;
use App\Models\File;
use App\Models\ItemChannel;
use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    /**
     * Register and Update Item
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(ItemRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            $item_no = $request->get('item_no');
            if (!isset($item_no)) {
                $item_no = Item::insertGetId([
                    'mb_no' => Auth::user()->mb_no,
                    'item_brand' => $validated['item_brand'],
                    'item_name' => $validated['item_name'],
                    'item_option1' => $validated['item_option1'],
                    'item_option2' => $validated['item_option2'],
                    'item_cargo_bar_code' => $validated['item_cargo_bar_code'],
                    'item_upc_code' => $validated['item_upc_code'],
                    'item_bar_code' => $validated['item_bar_code'],
                    'item_weight' => $validated['item_weight'],
                    'item_price1' => $validated['item_price1'],
                    'item_price2' => $validated['item_price2'],
                    'item_price3' => $validated['item_price3'],
                    'item_price4' => $validated['item_price4'],
                    'item_cate1' => $validated['item_cate1'],
                    'item_cate2' => $validated['item_cate2'],
                    'item_cate3' => $validated['item_cate3'],
                    'item_url' => $validated['item_url'],
                    'item_table' => '', // FIXME no information,
                    'item_key' => 0 // FIXME no information,
                ]);

                $item_channels = [];
                foreach ($validated['item_channels'] as $item_channel) {
                    if (isset($item_channel['item_channel_code']) && isset($item_channel['item_channel_name'])) {
                        $item_channels[] = [
                            'item_no' => $item_no,
                            'item_channel_code' => $item_channel['item_channel_code'],
                            'item_channel_name' => $item_channel['item_channel_name']
                        ];
                    }
                }
                ItemChannel::insert($item_channels);
            } else {
                // Update data
                $item = Item::with('file')->where('item_no', $item_no)->first();
                if (is_null($item)) {
                    return response()->json(['message' => Messages::MSG_0020], 404);
                }

                $update = [
                    'mb_no' => Auth::user()->mb_no,
                    'item_brand' => $validated['item_brand'],
                    'item_name' => $validated['item_name'],
                    'item_option1' => $validated['item_option1'],
                    'item_option2' => $validated['item_option2'],
                    'item_cargo_bar_code' => $validated['item_cargo_bar_code'],
                    'item_upc_code' => $validated['item_upc_code'],
                    'item_bar_code' => $validated['item_bar_code'],
                    'item_weight' => $validated['item_weight'],
                    'item_price1' => $validated['item_price1'],
                    'item_price2' => $validated['item_price2'],
                    'item_price3' => $validated['item_price3'],
                    'item_price4' => $validated['item_price4'],
                    'item_cate1' => $validated['item_cate1'],
                    'item_cate2' => $validated['item_cate2'],
                    'item_cate3' => $validated['item_cate3'],
                    'item_url' => $validated['item_url'],
                    'item_table' => '', // FIXME no information,
                    'item_key' => 0 // FIXME no information,
                ];
                $item->update($update);

                foreach ($validated['item_channels'] as $item_channel) {
                    ItemChannel::updateOrCreate(
                        [
                            'item_channel_no' => $item_channel['item_channel_no'] ?: null,
                        ],
                        [
                            'item_no' => $item_no,
                            'item_channel_code' => $item_channel['item_channel_code'],
                            'item_channel_name' => $item_channel['item_channel_name']
                        ]
                    );
                }
            }

            // Insert file
            if (isset($validated['file'])) {
                $path = join('/', ['files', 'item', $item_no]);
                $url = Storage::disk('public')->put($path, $validated['file']);

                File::insert([
                    'file_table' => 'item',
                    'file_table_key' => $item_no,
                    'file_name_old' => $validated['file']->getClientOriginalName(),
                    'file_name' => basename($url),
                    'file_size' => $validated['file']->getSize(),
                    'file_extension' => $validated['file']->extension(),
                    'file_position' => 0,
                    'file_url' => $url
                ]);
            }
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }

    public function searchItems(ItemSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $item = Item::with('file');

            $item = $item->paginate($per_page, ['*'], 'page', $page);
            return response()->json($item);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }


    public function getItemById(Item $item)
    {
        try {
            $file = $item->file()->first();
            $item_channels = $item->item_channels()->get();
            $item['item_channels'] = $item_channels;
            $item['file'] = $file;
            return response()->json($item);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function deleteItemChannel(ItemChannel $itemChannel) {
        try {
            $itemChannel->delete();
            return response()->json([
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0006], 500);
        }
    }
}
