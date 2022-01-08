<?php

namespace App\Http\Controllers;

use App\Mail\email_confirm_message;
use App\Mail\email_read_message;
use App\Mail\email_message_readed;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class Main extends Controller
{
    public function index(){
        return view('message_form');
    }

    public function init(Request $request){

        // check if there was a post
        if(!$request->isMethod('post')){
            return redirect()->route('main_index');
        }

        // validation
        $request->validate(
            [
                'text_from' => 'required|email|max:50',
                'text_to' => 'required|email|max:50',
                'text_message' => 'required|max:250'
            ],
            [
                'text_from.required' => 'From is required',
                'text_from.email' => 'From must be a valid email',
                'text_from.max' => 'From must have less than 50 chars',
                'text_to.required' => 'To is required',
                'text_to.email' => 'To must be a valid email',
                'text_to.max' => 'To must have less than 50 chars',
                'text_message.required' => 'Message is required',
                'text_message.max' => 'Message must have less than 250 chars'
            ]
        );

        // create hash code (purl code)
        $purl_code = Str::random(32);

        $message = new Message();
        $message->send_from = $request->text_from;
        $message->send_to = $request->text_to;
        $message->message = $request->text_message;
        $message->purl_confirmation = $purl_code;
        $message->save();

        /*
            1. criar a hash
            2. guardar a nova mensagem na base de dados
            3. enviar um email para o criador da mensagem com o link para confirmar
        */

        // send email to confirm message
        Mail::to($request->text_from)->send(new email_confirm_message($purl_code));

        // update da bd com a data e hora em que o email de confirmação foi enviado
        $message = Message::where('purl_confirmation', $purl_code)->first();
        $message->purl_confirmation_sent = now();
        $message->save();

        // apresentar a view com a indicação que o email de confirmação foi enviado
        $data = [
            'email_address' => $request->text_from
        ];
        return view('email_confirmation_sent', $data);
    }

    public function confirm($purl){

        /*
        - verifica se foi identificado o purl
        - verifica se o purl_confirmation existe no model
        - remove o purl_confirmation
        - cria um novo purl (purl_read) e guarda na base de dados
        - constroi o email para enviar para o destinatário da mensagem
        - envia o email e guarda na base de dados o purl_read_sent
        - apresenta uma view indicando que a mensagem foi confirmada e enviada para o destinatário
        */

        // check if purl exists
        if(empty($purl)){
            return redirect()->route('main_index');
        }

        // check if purl exists in db
        $message = Message::where('purl_confirmation', $purl)->first();

        // check if there is a message
        if($message === null){

            // apresentar uma view indicando que houve error
            // TODO - fazer a view
            return view('error');
        }

        // get the recipient email adress
        $email_to = $message->send_to;

        // remove purl_confirmation and creates purl_read
        $purl_code = Str::random(32);
        $message->purl_confirmation = null;
        $message->purl_read = $purl_code;
        $message->purl_read_sent = now();
        $message->save();

        // send email to the recipient
        Mail::to($email_to)->send(new email_read_message($purl_code));

        // apresentar a view com a indicação que a mensagem foi enviada com sucesso
        $data = [
            'email_address' => $email_to
        ];
        return view('message_sent', $data);
    }

    public function read($purl){

        // check if purl exists
        if(empty($purl)){
            return redirect()->route('main_index');
        }

        // check if purl exists in db
        $message = Message::where('purl_read', $purl)->first();

        // check if there is a message
        if($message === null){

            // apresentar uma view indicando que houve error
            // apresentar a view com a indicação que a mensagem foi enviada com sucesso
            return view('error');
        }

        // remove purl_read and store message_readed
        $message_readed = now();
        $email_recipient = $message->send_to;
        $email_from = $message->send_from;

        $message->purl_read = null;
        $message->message_readed = $message_readed;
        $message->save();

        // send email to the emitter confirming that the message was readed
        Mail::to($email_from)->send(new email_message_readed($email_recipient, $message_readed));

        // display the one time message

        $data = [
            'mensagem' => $message->message,
            'emissor' => $message->send_from
        ];
        return view('read_message', $data);
    }
}
