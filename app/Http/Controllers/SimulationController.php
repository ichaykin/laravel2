<?php

namespace App\Http\Controllers;

use App\Lottery;
use App\Round;
use App\Ticket;
use App\User;
use App\WinningTicket;
use Faker\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function GuzzleHttp\Promise\queue;

class SimulationController extends Controller
{
    private $lottery_number;
    private $users_count;
    private $bank;
    private $users;
    private $tickets;
    private $remaining_numbers;
    private $round;

    private function setUp($users_count, $bank)
    {
        $lottery = new Lottery();
        $lottery->save();
        $this->lottery_number = $lottery->lottery_id;
        $this->users_count = $users_count;
        $this->bank = $bank;
        $this->users = factory(User::class, $this->users_count)->create();

        foreach ($this->users as $user) {
            $ticket = new Ticket(['lottery_id' => $this->lottery_number,'user_id' => $user->user_id]);
            $ticket->save();
            $this->tickets[] = $ticket;
        }

        $remaining_numbers = range(1,90);
        shuffle($remaining_numbers);
        $this->remaining_numbers = $remaining_numbers;
    }

    public function start($users_count, $bank)
    {
        $this->setUp($users_count, $bank);

        while (!empty($this->remaining_numbers)) {
            $round = new Round();
            $round->lottery_id = $this->lottery_number;
            $round->number = array_shift($this->remaining_numbers);
            $round->save();

            $this->setNumberInTickets($round->number);
            $this->checkWinningTickets($round->round_id);
        }

        $top_ten_users = DB::select(DB::raw("SELECT users.*, amount FROM winning_tickets LEFT JOIN tickets ON winning_tickets.ticket_id = tickets.ticket_id LEFT JOIN users ON users.user_id = tickets.user_id WHERE tickets.lottery_id = {$this->lottery_number} ORDER BY amount DESC LIMIT 10"));

        return ['lottery_number' => $this->lottery_number, 'top_ten_users' => $top_ten_users];
    }

    private function setNumberInTickets($number)
    {
        foreach ($this->tickets as &$ticket) {
            $fields = unserialize($ticket->numbers);

            foreach ($fields as &$field) {
                foreach ($field as &$row) {
                    foreach ($row as &$item) {
                        if ($item['number'] === $number) {
                            $item['is_played'] = true;
                            $ticket->numbers = serialize($fields);
                            $ticket->save();

                            break(3);
                        }
                    }
                }
            }
        }
    }

    private function checkWinningTickets($round_id)
    {
        $count_first_tour = $count_second_tour = $count_third_tour = 0;

        $winning_tickets = [];
        foreach ($this->tickets as $ticket) {
            if (!$ticket->is_winning && $ticket->is_winning_first_tour()) {
                $winning_ticket = new WinningTicket();
                $winning_ticket->ticket_id = $ticket->ticket_id;
                $winning_ticket->round_id = $round_id;
                $winning_ticket->tour = 1;

                $winning_tickets[] = $winning_ticket;
            }
        }

        $this->saveWinningTickets($winning_tickets);

        $winning_tickets = [];
        foreach ($this->tickets as $ticket) {
            if (!$ticket->is_winning && $ticket->is_winning_second_tour()) {
                $winning_ticket = new WinningTicket();
                $winning_ticket->ticket_id = $ticket->ticket_id;
                $winning_ticket->round_id = $round_id;
                $winning_ticket->tour = 2;

                $winning_tickets[] = $winning_ticket;
            }
        }

        //$this->saveWinningTickets($winning_tickets);

        $winning_tickets = [];
        foreach ($this->tickets as $ticket) {
            if (!$ticket->is_winning && $ticket->is_winning_third_tour()) {
                $winning_ticket = new WinningTicket();
                $winning_ticket->ticket_id = $ticket->ticket_id;
                $winning_ticket->round_id = $round_id;
                $winning_ticket->tour = 3;

                $winning_tickets[] = $winning_ticket;
            }
        }

        //$this->saveWinningTickets($winning_tickets);
    }

    private function saveWinningTickets($winning_tickets)
    {
        if (!empty($winning_tickets)) {
            $amount = $this->bank * 0.5 / count($winning_tickets);
            $this->bank = $this->bank * 0.5;

            foreach ($winning_tickets as $winning_ticket) {
                $winning_ticket->amount = $amount;
                $winning_ticket->save();
            }
        }
    }
}
