<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class ExampleWhatsAppUsageController extends Controller
{
    private $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Ejemplo: Enviar mensaje de bienvenida
     */
    public function sendWelcomeMessage(Request $request)
    {
        $phone = $request->input('phone');
        $name = $request->input('name', 'Cliente');

        $message = "¡Hola {$name}! 👋\n\n";
        $message .= "Bienvenido al U.E JOSE MARIA VARGAS.\n";
        $message .= "Estamos aquí para ayudarte con tus consultas.\n\n";
        $message .= "¿En qué podemos asistirte hoy?";

        $result = $this->whatsappService->sendMessage($phone, $message);

        return response()->json([
            'success' => $result !== null && ($result['success'] ?? false),
            'message' => $result ? 'Mensaje enviado correctamente' : 'Error al enviar mensaje',
            'data' => $result
        ]);
    }

    /**
     * Ejemplo: Enviar recordatorio de cita
     */
    public function sendAppointmentReminder(Request $request)
    {
        $phone = $request->input('phone');
        $date = $request->input('date');
        $time = $request->input('time');
        $service = $request->input('service');

        $message = "📅 *Recordatorio de Cita*\n\n";
        $message .= "Tienes una cita programada:\n";
        $message .= "🗓️ Fecha: {$date}\n";
        $message .= "⏰ Hora: {$time}\n";
        $message .= "📋 Servicio: {$service}\n\n";
        $message .= "Por favor confirma tu asistencia respondiendo *SÍ* o *NO*.\n\n";
        $message .= "U.E JOSE MARIA VARGAS";

        $result = $this->whatsappService->sendMessage($phone, $message);

        return response()->json([
            'success' => $result !== null && ($result['success'] ?? false),
            'message' => $result ? 'Recordatorio enviado' : 'Error al enviar recordatorio'
        ]);
    }

    /**
     * Ejemplo: Dashboard de WhatsApp
     */
    public function dashboard()
    {
        $status = $this->whatsappService->getStatus();
        $messages = $this->whatsappService->getMessages(['limit' => 10]);

        return view('whatsapp.dashboard', compact('status', 'messages'));
    }
}