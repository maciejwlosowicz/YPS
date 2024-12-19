<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\RomanNumeralConverter;
use App\Models\RomanNumeral;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class RomanNumeralsController extends Controller
{
    //Lists all the recently converted integers.
    public function index()
    {
        $fractal = new Manager();

        $items = RomanNumeral::orderBy('created_at', 'DESC')->get()->toArray();

        $resource = new Collection($items, function(array $item) {
            return [
                // 'id'         => (int) $item['id'],
                'standard'   => (int) $item['standard'],
                // 'roman'      => $item['roman'],
                // 'created_at' => $item['created_at'],
                // 'updated_at' => $item['updated_at'],
            ];
        });

        return $fractal->createData($resource)->toJson();
    }

    //Accepts an integer, converts it to a Roman numeral, stores it in the database and returns the response.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|integer|min:1|max:3999',
        ]);

        if ($validator->fails()) {
            return redirect('/')
                        ->withErrors($validator)
                        ->withInput();
        }

        $validated = $validator->validated();
        $converter = new RomanNumeralConverter();
        $roman = $converter->convertInteger($validated['value']);

        $table = new RomanNumeral;
        $table->standard = $validated['value'];
        $table->roman = $roman;
        $table->save();

        return response()->json($table->roman);
    }

    //Lists the top 10 converted integers.
    public function topten()
    {
        $fractal = new Manager();

        $items = DB::table('roman_numerals')
            ->select('standard', DB::raw('COUNT(*) AS count'), )
            ->groupBy('standard')
            ->orderByRaw('COUNT(*) DESC')
            ->take(10)
            ->get()
            ->map(fn ($row) => get_object_vars($row))
            ->toArray();

        $resource = new Collection($items, function(array $item) {
            return [
                // 'id'         => (int) $item['id'],
                'standard'   => (int) $item['standard'],
                // 'roman'      => $item['roman'],
                // 'created_at' => $item['created_at'],
                // 'updated_at' => $item['updated_at'],
                // 'count'      => (int) $item['count'],
            ];
        });

        return $fractal->createData($resource)->toJson();
    }
}
