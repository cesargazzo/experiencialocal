<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\ExperienceStatus;
use App\Enums\HostStatus;
use App\Enums\VerificationLevel;
use App\Enums\VerificationProvider;
use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Experience;
use App\Models\HostProfile;
use App\Models\Plan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Datos demostrativos, equivalentes a los del prototipo estático.
 * No se carga en producción.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $admin = User::updateOrCreate(['email' => 'admin@tinku.test'], [
            'name' => 'Equipo Tinku', 'password' => 'password', 'is_admin' => true, 'country_code' => 'AR',
            'email_verified_at' => now(), 'verification_level' => VerificationLevel::Residence,
        ]);

        $hosts = [
            ['Marta Quiroga', 'marta@tinku.test', 'La Rioja', 'AR', 'free', 3, 2024, 'Cocinera de familia, docente jubilada. Recibo en mi casa del barrio San Martín desde hace dos años.'],
            ['Giuliana Ferrero', 'giuliana@tinku.test', 'La Rioja', 'AR', 'impulso', 2, 2025, 'Nieta de inmigrantes calabreses. Doy clases de pasta en la cocina de mi abuela, que sigue siendo la misma.'],
            ['Rodrigo Salas', 'rodrigo@tinku.test', 'La Rioja', 'PE', 'impulso', 3, 2025, 'Limeño, cocinero de profesión. Hace cinco años que vivo en La Rioja y extraño el ceviche, así que lo hago yo.'],
            ['Pablo Herrera', 'pablo@tinku.test', 'Chilecito', 'AR', 'pro', 3, 2024, 'Nací en Chilecito y conozco cada curva de la cuesta. Guía habilitado y productor de aceite de oliva.'],
            ['Julieta Moreno', 'julieta@tinku.test', 'La Rioja', 'AR', 'free', 2, 2025, 'Panadera autodidacta. Empecé en pandemia y hoy vivo de esto. Mi horno de barro lo construimos con mi papá.'],
            ['Elena Ruiz', 'elena@tinku.test', 'Nonogasta', 'AR', 'pro', 3, 2024, 'Tercera generación en la bodega. Hago los vinos con mi hermano y recibo a quienes quieran conocerlos.'],
        ];

        $profiles = [];
        foreach ($hosts as [$name, $email, $city, $nationality, $plan, $level, $since, $bio]) {
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name, 'password' => 'password', 'country_code' => 'AR', 'nationality_code' => $nationality,
                'phone' => '+54 380 4'.random_int(100000, 999999), 'email_verified_at' => now(), 'phone_verified_at' => now(),
            ]);
            $this->seedVerifications($user, $level, $nationality, $admin);
            $profiles[$name] = HostProfile::updateOrCreate(['user_id' => $user->id], [
                'plan_id' => Plan::where('slug', $plan)->value('id'),
                'display_name' => $name, 'bio' => $bio, 'city' => $city, 'province' => 'La Rioja', 'country_code' => 'AR',
                'status' => HostStatus::Active, 'hosting_since' => now()->setYear($since)->startOfYear(),
                'payout_holder_name' => $name,
            ]);
        }

        $guests = collect([
            ['Lucía Paz', 'lucia@tinku.test', 'AR'], ['Tomás Rey', 'tomas@tinku.test', 'AR'], ['Ana Molina', 'ana@tinku.test', 'AR'],
            ['Federico Luna', 'federico@tinku.test', 'AR'], ['Carla Díaz', 'carla@tinku.test', 'UY'], ['Malena Gómez', 'malena@tinku.test', 'AR'],
            ['Ignacio Bruno', 'ignacio@tinku.test', 'ES'], ['Sofía Acosta', 'sofia@tinku.test', 'AR'], ['Ramiro Castro', 'ramiro@tinku.test', 'BR'],
            ['Valentina Sosa', 'valentina@tinku.test', 'AR'], ['Martín Ortiz', 'martin@tinku.test', 'CL'],
        ])->mapWithKeys(function ($g) use ($admin) {
            [$name, $email, $nationality] = $g;
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name, 'password' => 'password', 'nationality_code' => $nationality, 'country_code' => $nationality,
                'email_verified_at' => now(), 'phone_verified_at' => now(), 'phone' => '+54 11 4'.random_int(1000000, 9999999),
            ]);
            $this->seedVerifications($user, 2, $nationality, $admin);

            return [$name => $user];
        });

        $cat = fn (string $slug) => Category::where('slug', $slug)->value('id');
        $experiences = [
            [
                'host' => 'Marta Quiroga', 'cat' => 'comida', 'title' => 'Sabores riojanos en el patio', 'type' => 'Cocina regional', 'city' => 'La Rioja',
                'summary' => 'Empanadas, cabrito y sobremesa con recetas de familia.',
                'description' => 'Una cena en el patio de una casa de barrio, con recetas que pasaron por tres generaciones. Cocinamos juntos las empanadas, compartimos el cabrito al horno de barro y cerramos con dulces caseros y una guitarra si se da.',
                'price' => 40000, 'minutes' => 210, 'guests' => 8, 'image' => 'https://images.unsplash.com/photo-1529543544282-ea669407fca3?auto=format&fit=crop&w=1200&q=70',
                'includes' => [['Recepción', 'Vermú con aceitunas y quesos regionales'], ['Principal', 'Empanadas riojanas y cabrito al horno de barro'], ['Postre', 'Dulce de cayote con nuez y café de olla'], ['Bebida', 'Vino torrontés de la zona, sin límite']],
                'dates' => [['+6 days 20:30'], ['+13 days 20:30'], ['+19 days 21:00']],
                'reviews' => [['Lucía Paz', 5, 'La comida increíble, pero lo mejor fue la charla. Nos fuimos a la una de la mañana.'], ['Tomás Rey', 5, 'Vinimos de Córdoba y fue el punto alto del viaje. Marta es una anfitriona de otra época.'], ['Ana Molina', 4, 'Muy buena experiencia. Las empanadas, de las mejores que probé.']],
            ],
            [
                'host' => 'Giuliana Ferrero', 'cat' => 'cocina', 'title' => 'Pastas de la nonna', 'type' => 'Clase de cocina italiana', 'city' => 'La Rioja',
                'summary' => 'Preparación artesanal, cena compartida y una historia familiar.',
                'description' => 'Amasamos desde cero tallarines, ravioles y ñoquis con la receta de la nonna Rosa, que llegó de Calabria en 1952. Después cenamos lo que hicimos, con salsa de tomate de la huerta y vino de la casa.',
                'price' => 35000, 'minutes' => 240, 'guests' => 6, 'image' => 'https://images.unsplash.com/photo-1556910103-1c02745aae4d?auto=format&fit=crop&w=1200&q=70',
                'includes' => [['Clase', 'Masa, relleno y corte de tres tipos de pasta'], ['Cena', 'Lo que cocinamos, con salsas caseras'], ['Para llevar', 'Recetario impreso y medio kilo de pasta fresca'], ['Bebida', 'Vino de la casa y agua']],
                'dates' => [['+7 days 11:00'], ['+14 days 11:00'], ['+21 days 11:00']],
                'reviews' => [['Federico Luna', 5, 'Aprendí más en cuatro horas que en años de videos. Y la cena fue una fiesta.'], ['Carla Díaz', 5, 'Giuliana explica con paciencia y cariño. Volvería sin dudar.']],
            ],
            [
                'host' => 'Rodrigo Salas', 'cat' => 'comida', 'title' => 'Ceviche y cocina criolla', 'type' => 'Cocina peruana', 'city' => 'La Rioja',
                'summary' => 'Una cena guiada para conocer ingredientes, técnicas y cultura.',
                'description' => 'Una mesa de seis pasos que recorre la cocina peruana: ceviche clásico, causa limeña, lomo saltado y suspiro a la limeña. Cada plato viene con su historia y con la explicación de qué ingredientes se consiguen acá y cuáles traigo de Lima.',
                'price' => 45000, 'minutes' => 180, 'guests' => 10, 'image' => 'https://images.unsplash.com/photo-1535399831218-d5bd36d1a6b3?auto=format&fit=crop&w=1200&q=70',
                'includes' => [['Entrada', 'Ceviche clásico y causa limeña'], ['Principal', 'Lomo saltado y ají de gallina'], ['Postre', 'Suspiro a la limeña'], ['Bebida', 'Pisco sour de bienvenida y chicha morada']],
                'dates' => [['+5 days 21:00'], ['+12 days 21:00'], ['+20 days 21:00']],
                'reviews' => [['Malena Gómez', 5, 'El mejor ceviche que comí fuera de Perú. Rodrigo cuenta cada plato como si fuera un cuento.']],
            ],
            [
                'host' => 'Pablo Herrera', 'cat' => 'paseo', 'title' => 'Cuesta de Miranda con almuerzo de campo', 'type' => 'Paseo de día completo', 'city' => 'Chilecito',
                'summary' => 'Un día por la cuesta, la mina y una mesa larga en una finca.',
                'description' => 'Salimos temprano desde Chilecito, recorremos la Cuesta de Miranda con paradas para fotos y caminatas cortas, visitamos el cable carril y almorzamos en la finca de la familia de Pablo con productos de la huerta y asado al asador.',
                'price' => 65000, 'minutes' => 540, 'guests' => 12, 'image' => 'https://images.unsplash.com/photo-1501555088652-021faa106b9b?auto=format&fit=crop&w=1200&q=70',
                'includes' => [['Traslado', 'Camioneta desde Chilecito, ida y vuelta'], ['Recorrido', 'Cuesta de Miranda y cable carril con guía'], ['Almuerzo', 'Asado al asador y verduras de huerta en la finca'], ['Bebida', 'Vino de bodega familiar y agua']],
                'dates' => [['+6 days 08:00'], ['+13 days 08:00'], ['+21 days 08:00']],
                'reviews' => [['Ignacio Bruno', 5, 'El almuerzo en la finca es lo que uno se imagina cuando piensa en el norte. Impecable.'], ['Sofía Acosta', 5, 'Pablo sabe de todo y no apura a nadie. El paisaje habla solo.']],
            ],
            [
                'host' => 'Julieta Moreno', 'cat' => 'taller', 'title' => 'Pan de masa madre en horno de barro', 'type' => 'Taller de panadería', 'city' => 'La Rioja',
                'summary' => 'Amasado, fermentación y horneado. Te llevás tu pan y tu masa madre.',
                'description' => 'Un taller de mañana para entender la masa madre sin misterio: alimentación, plegados, formado y horneado en horno de barro. Desayunamos con lo que salió del horno y te llevás un frasco de masa madre viva.',
                'price' => 28000, 'minutes' => 300, 'guests' => 8, 'image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1200&q=70',
                'includes' => [['Taller', 'Masa madre, plegados, formado y horneado'], ['Desayuno', 'Pan recién salido, manteca y dulces caseros'], ['Para llevar', 'Un pan y un frasco de masa madre activa'], ['Material', 'Guía impresa con tiempos y temperaturas']],
                'dates' => [['+6 days 09:00'], ['+20 days 09:00']],
                'reviews' => [['Ramiro Castro', 5, 'Por fin entendí la masa madre. Ya hice tres panes en casa y salieron bien.'], ['Valentina Sosa', 4, 'Muy completo. Cinco horas que pasan volando.']],
            ],
            [
                'host' => 'Elena Ruiz', 'cat' => 'paseo', 'title' => 'Cata en bodega familiar al atardecer', 'type' => 'Visita y cata', 'city' => 'Nonogasta',
                'summary' => 'Recorrido por la viña, cata de cuatro vinos y picada entre los parrales.',
                'description' => 'Caminamos la viña con Elena, que la trabaja con su familia desde 1978. Probamos cuatro vinos directamente en la bodega y cerramos con una picada de quesos, fiambres y aceitunas de la zona mientras baja el sol.',
                'price' => 32000, 'minutes' => 180, 'guests' => 14, 'image' => 'https://images.unsplash.com/photo-1506377247377-2a5b3b417ebb?auto=format&fit=crop&w=1200&q=70',
                'includes' => [['Recorrido', 'Viña, bodega y sala de barricas'], ['Cata', 'Cuatro vinos con explicación'], ['Picada', 'Quesos, fiambres y aceitunas regionales'], ['Para llevar', 'Una botella a elección con descuento']],
                'dates' => [['+5 days 18:00'], ['+13 days 18:00'], ['+19 days 18:00']],
                'reviews' => [['Martín Ortiz', 5, 'El atardecer entre los parrales no tiene precio. Y el torrontés tampoco.']],
            ],
        ];

        foreach ($experiences as $data) {
            $experience = Experience::updateOrCreate(['slug' => Str::slug($data['title'])], [
                'host_profile_id' => $profiles[$data['host']]->id,
                'category_id' => $cat($data['cat']),
                'title' => $data['title'], 'type_label' => $data['type'], 'summary' => $data['summary'], 'description' => $data['description'],
                'city' => $data['city'], 'province' => 'La Rioja', 'country_code' => 'AR',
                'price' => $data['price'], 'currency' => 'ARS', 'duration_minutes' => $data['minutes'], 'max_guests' => $data['guests'],
                'includes' => array_map(fn ($i) => ['label' => $i[0], 'text' => $i[1]], $data['includes']),
                'cover_image_url' => $data['image'], 'status' => ExperienceStatus::Published, 'published_at' => now()->subMonths(3),
            ]);

            if ($experience->dates()->doesntExist()) {
                foreach ($data['dates'] as [$when]) {
                    $time = substr($when, strrpos($when, ' ') + 1);
                    $offset = substr($when, 0, strrpos($when, ' '));
                    $start = now()->modify($offset)->setTimeFromTimeString($time);
                    $experience->dates()->create(['starts_at' => $start, 'ends_at' => $start->copy()->addMinutes($data['minutes']), 'capacity' => $data['guests']]);
                }
            }

            // Opiniones: siempre nacen de una reserva completada en una fecha pasada.
            if ($experience->reviews()->doesntExist()) {
                $pastDate = $experience->dates()->create(['starts_at' => now()->subWeeks(3), 'capacity' => $data['guests'], 'status' => 'done']);
                foreach ($data['reviews'] as $i => [$guestName, $rating, $body]) {
                    $guest = $guests[$guestName];
                    $subtotal = $data['price'] * 2;
                    $rate = (float) $profiles[$data['host']]->plan->commission_rate;
                    $booking = Booking::create([
                        'experience_date_id' => $pastDate->id, 'experience_id' => $experience->id, 'user_id' => $guest->id, 'guests' => 2,
                        'unit_price' => $data['price'], 'subtotal' => $subtotal, 'service_fee_rate' => 0.05, 'service_fee' => $subtotal * 0.05,
                        'total' => $subtotal * 1.05, 'commission_rate' => $rate, 'commission_amount' => $subtotal * $rate, 'host_payout' => $subtotal * (1 - $rate),
                        'status' => BookingStatus::Completed, 'confirmed_at' => now()->subWeeks(4), 'paid_at' => now()->subWeeks(4), 'completed_at' => now()->subWeeks(3),
                    ]);
                    $pastDate->increment('booked_count', 2);
                    Review::create(['booking_id' => $booking->id, 'experience_id' => $experience->id, 'user_id' => $guest->id, 'rating' => $rating, 'body' => $body, 'published_at' => now()->subWeeks(3)->addDays($i)]);
                }
                $experience->refreshRating();
            }
        }
    }

    private function seedVerifications(User $user, int $level, string $nationality, User $admin): void
    {
        $types = [VerificationType::Email, VerificationType::Phone];
        if ($level >= 2) {
            $types[] = VerificationType::Document;
            $types[] = VerificationType::Liveness;
        }
        if ($level >= 3) {
            $types[] = VerificationType::Address;
        }
        foreach ($types as $type) {
            $isDoc = in_array($type, [VerificationType::Document, VerificationType::Liveness], true);
            $user->verifications()->updateOrCreate(['type' => $type], [
                'provider' => match (true) {
                    $isDoc => VerificationProvider::forDocumentCountry($nationality),
                    $type->level() === VerificationLevel::Residence => VerificationProvider::Manual,
                    default => VerificationProvider::Internal,
                },
                'status' => VerificationStatus::Approved,
                'document_country' => $isDoc ? $nationality : null,
                'document_type' => $type === VerificationType::Document ? ($nationality === 'AR' ? 'dni' : 'passport') : null,
                'document_hash' => $type === VerificationType::Document ? hash('sha256', $nationality.'|'.$user->email) : null,
                'reviewed_by' => $type->level() === VerificationLevel::Residence ? $admin->id : null,
                'submitted_at' => now()->subMonths(2), 'reviewed_at' => now()->subMonths(2),
            ]);
        }
        $user->forceFill(['verification_level' => VerificationLevel::from($level)])->save();
    }
}
