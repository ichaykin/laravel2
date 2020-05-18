<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $primaryKey = 'ticket_id';

    protected $fillable = ['lottery_id', 'user_id'];

    public function __construct(array $attributes = [])
    {
        $this->generateTicket();
        parent::__construct($attributes);
    }

    private function generateTicket($fields_count = 2) {
        $fields = [];
        for ($i = 0; $i < $fields_count; $i++) {
            $fields[] = $this->fillField();
        }

        $this->numbers = serialize($fields);
    }

    private function fillField()
    {
        $rows = 3;
        $columns = 9;
        $field = [];

        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $columns; $j++) {
                $field[$i][$j] = null;
            }
        }

        for($i = 0; $i < $rows; $i++) {
            $el_count = 0;
            while ($el_count < 5) {
                $column = rand(0, $columns - 1);

                if (!$field[$i][$column]) {
                    $number = rand($column*10 + 1,($column+1)*10);

                    if (in_array($number, Arr::flatten($field))) {
                        continue;
                    }

                    $field[$i][$column] = [
                        'number'     => $number,
                        'is_played' => false
                        ];
                    $el_count++;
                }
            }
        }

        return $field;
    }

    public function is_winning_first_tour()
    {
        $fields = unserialize($this->numbers);

        foreach ($fields as $field) {
            foreach ($field as $row) {
                $played_numbers = Arr::where($row, function ($item) {
                    return $item['is_played'];
                });

                if (count($played_numbers) === 5) {
                    $this->is_winning = true;
                    $this->save();

                    break(2);
                }
            }
        }

        return $this->is_winning;
    }

    public function is_winning_second_tour()
    {
        return false;
    }

    public function is_winning_third_tour()
    {
        return false;
    }
}
