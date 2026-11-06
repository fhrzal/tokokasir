<?php

namespace Tests\Feature;

use App\Models\ShiftSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function login(User $user)
    {
        return $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);
    }

    // ---- Masuk sesuai jadwal ---------------------------------------------------

    public function test_kasir_without_schedule_cannot_login(): void
    {
        $kasir = $this->user();

        $this->login($kasir)->assertSessionHasErrors(['email' => 'Anda tidak memiliki jadwal shift saat ini. Hubungi admin atau pemilik toko.']);
        $this->assertGuest();
    }

    public function test_kasir_can_login_only_within_tolerance_before_schedule_starts(): void
    {
        $kasir = $this->user();
        $this->schedule($kasir, 30, 510); // mulai 30 menit lagi, toleransi 15 menit

        $this->login($kasir)->assertSessionHasErrors('email');
        $this->assertStringContainsString('Jadwal berikutnya', session('errors')->first('email'));
        $this->assertGuest();

        $this->travel(16)->minutes();
        $this->login($kasir)->assertRedirect(route('kasir.pos'));
        $this->assertAuthenticatedAs($kasir);
    }

    public function test_kasir_cannot_login_after_schedule_ends(): void
    {
        $kasir = $this->user();
        $this->schedule($kasir, -480, -1);

        $this->login($kasir)->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_kasir_with_unclosed_shift_can_login_to_close_it(): void
    {
        $kasir = $this->user();
        $this->openShift($kasir, 100000, $this->schedule($kasir, -540, -60));

        $this->login($kasir)->assertRedirect(route('kasir.pos'));
        $this->get(route('kasir.pos'))->assertRedirect(route('kasir.shift.edit'));
        $this->get(route('kasir.shift.edit'))->assertOk()->assertSee('Tutup Shift');
    }

    public function test_session_without_schedule_is_ended(): void
    {
        $kasir = $this->user();
        $schedule = $this->schedule($kasir, -60, 30);
        $this->actingAs($kasir)->get(route('kasir.shift.create'))->assertOk();

        $this->travelTo($schedule->waktu_selesai->addMinute());

        $this->get(route('kasir.shift.create'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_and_owner_are_not_restricted_by_schedule(): void
    {
        $this->login($this->user(User::ROLE_ADMIN))->assertRedirect(route('admin.dashboard'));
        $this->post(route('logout'));
        $this->login($this->user(User::ROLE_PEMILIK))->assertRedirect(route('pemilik.dashboard'));
    }

    // ---- Buka, transaksi, tutup, keluar ----------------------------------------

    public function test_opening_shift_is_linked_to_current_schedule(): void
    {
        $kasir = $this->user();
        $schedule = $this->schedule($kasir);

        $this->actingAs($kasir)->post(route('kasir.shift.store'), ['kas_awal' => 150000])
            ->assertRedirect(route('kasir.pos'));

        $this->assertSame($schedule->schedule_id, $kasir->openShift()->first()->schedule_id);
    }

    public function test_schedule_can_only_be_used_for_one_shift(): void
    {
        $kasir = $this->user();
        $schedule = $this->schedule($kasir, -480, 5);
        $shift = $this->openShift($kasir, 100000, $schedule);

        $this->actingAs($kasir)->post(route('kasir.shift.close'), ['kas_akhir' => 100000])
            ->assertRedirect(route('kasir.shift.show', $shift));

        // Jadwal sudah terpakai: kasir tidak dapat membuka shift lagi.
        $this->post(route('kasir.shift.store'), ['kas_awal' => 100000])->assertRedirect(route('login'));
        $this->assertSame(1, $kasir->shifts()->count());
    }

    public function test_shift_cannot_be_closed_before_schedule_end(): void
    {
        $kasir = $this->user();
        $shift = $this->openShift($kasir, 100000, $this->schedule($kasir, -60, 120));

        $this->actingAs($kasir)->get(route('kasir.shift.edit'))->assertOk()->assertSee('Shift baru dapat ditutup mulai pukul');
        $this->post(route('kasir.shift.close'), ['kas_akhir' => 100000])->assertSessionHas('error');
        $this->assertTrue($shift->fresh()->isOpen());

        // Mulai 15 menit sebelum jadwal selesai, shift boleh ditutup.
        $this->travel(106)->minutes();
        $this->post(route('kasir.shift.close'), ['kas_akhir' => 100000])->assertRedirect(route('kasir.shift.show', $shift));
        $this->assertFalse($shift->fresh()->isOpen());
    }

    public function test_kasir_cannot_logout_while_shift_is_open(): void
    {
        $kasir = $this->user();
        $this->openShift($kasir, 100000, $this->schedule($kasir));

        $this->actingAs($kasir)->post(route('logout'))->assertRedirect(route('kasir.shift.edit'));
        $this->assertAuthenticatedAs($kasir);
    }

    public function test_sales_are_blocked_after_schedule_end_plus_tolerance(): void
    {
        $kasir = $this->user();
        $schedule = $this->schedule($kasir, -480, 10);
        $this->openShift($kasir, 100000, $schedule);
        $product = $this->product();
        $sale = ['items' => [['product_id' => $product->product_id, 'qty' => 1]], 'metode_bayar' => 'qris'];

        $this->actingAs($kasir);
        $this->travelTo($schedule->waktu_selesai->addMinutes(10));
        $this->postJson(route('kasir.pos.store'), $sale)->assertCreated();

        $this->travelTo($schedule->salesEndAt());
        $this->postJson(route('kasir.pos.store'), $sale)
            ->assertStatus(409)
            ->assertJsonPath('redirect', route('kasir.shift.edit'));
        $this->get(route('kasir.pos'))->assertRedirect(route('kasir.shift.edit'));
    }

    // ---- Pengelolaan jadwal ----------------------------------------------------

    public function test_only_admin_and_owner_can_manage_schedules(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))->get(route('jadwal.index'))->assertOk();
        $this->actingAs($this->user(User::ROLE_PEMILIK))->get(route('jadwal.index'))->assertOk();

        $kasir = $this->user();
        $this->schedule($kasir);
        $this->actingAs($kasir)->get(route('jadwal.index'))->assertForbidden();
    }

    public function test_admin_creates_schedules_for_selected_weekdays_in_a_range(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 06:00')); // Senin
        $admin = $this->user(User::ROLE_ADMIN);
        $kasir = $this->user();

        $this->actingAs($admin)->post(route('jadwal.store'), [
            'user_id' => $kasir->user_id,
            'tanggal_mulai' => '2026-11-09',
            'tanggal_selesai' => '2026-11-15',
            'hari' => [1, 3, 5], // Senin, Rabu, Jumat
            'jam_mulai' => '07:00',
            'jam_selesai' => '15:00',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(
            ['2026-11-09 07:00', '2026-11-11 07:00', '2026-11-13 07:00'],
            $kasir->schedules()->orderBy('waktu_mulai')->get()->map(fn ($s) => $s->waktu_mulai->format('Y-m-d H:i'))->all(),
        );
        $this->assertSame($admin->user_id, $kasir->schedules()->first()->dibuat_oleh);
    }

    public function test_overnight_schedule_ends_next_day(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 06:00'));
        $kasir = $this->user();

        $this->actingAs($this->user(User::ROLE_PEMILIK))->post(route('jadwal.store'), [
            'user_id' => $kasir->user_id,
            'tanggal_mulai' => '2026-11-09',
            'jam_mulai' => '22:00',
            'jam_selesai' => '06:00',
        ])->assertSessionHasNoErrors();

        $schedule = $kasir->schedules()->first();
        $this->assertSame('2026-11-10 06:00', $schedule->waktu_selesai->format('Y-m-d H:i'));
        $this->assertSame('22:00–06:00 (+1 hari)', $schedule->timeLabel());
    }

    public function test_overlapping_schedule_is_rejected(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 06:00'));
        $kasir = $this->user();
        $this->actingAs($this->user(User::ROLE_ADMIN));

        $payload = ['user_id' => $kasir->user_id, 'tanggal_mulai' => '2026-11-10', 'jam_mulai' => '07:00', 'jam_selesai' => '15:00'];
        $this->post(route('jadwal.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('jadwal.store'), ['jam_mulai' => '14:00', 'jam_selesai' => '21:00'] + $payload)->assertSessionHasErrors('jam_mulai');

        // Jadwal yang bersambung tidak dianggap bentrok.
        $this->post(route('jadwal.store'), ['jam_mulai' => '15:00', 'jam_selesai' => '21:00'] + $payload)->assertSessionHasNoErrors();
        $this->assertSame(2, $kasir->schedules()->count());
    }

    public function test_schedule_cannot_be_created_for_non_kasir_or_in_the_past(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 12:00'));
        $admin = $this->user(User::ROLE_ADMIN);
        $this->actingAs($admin);

        $this->post(route('jadwal.store'), ['user_id' => $admin->user_id, 'tanggal_mulai' => '2026-11-10', 'jam_mulai' => '07:00', 'jam_selesai' => '15:00'])
            ->assertSessionHasErrors('user_id');

        $kasir = $this->user();
        $this->post(route('jadwal.store'), ['user_id' => $kasir->user_id, 'tanggal_mulai' => '2026-11-09', 'jam_mulai' => '07:00', 'jam_selesai' => '11:00'])
            ->assertSessionHasErrors('tanggal_mulai');

        $this->assertSame(0, ShiftSchedule::count());
    }

    public function test_manager_can_shorten_running_schedule_so_kasir_can_leave_early(): void
    {
        $kasir = $this->user();
        $schedule = $this->schedule($kasir, -60, 300);
        $shift = $this->openShift($kasir, 100000, $schedule);

        $this->actingAs($this->user(User::ROLE_ADMIN))->get(route('jadwal.edit', $schedule))
            ->assertOk()->assertSee('sedang bertugas sejak');

        $this->put(route('jadwal.update', $schedule), [
            'tanggal' => $schedule->waktu_mulai->toDateString(),
            'jam_mulai' => $schedule->waktu_mulai->format('H:i'),
            'jam_selesai' => now()->addMinutes(5)->format('H:i'),
        ])->assertSessionHasNoErrors();

        $this->actingAs($kasir)->post(route('kasir.shift.close'), ['kas_akhir' => 100000])
            ->assertRedirect(route('kasir.shift.show', $shift));
    }

    public function test_started_or_past_schedules_cannot_be_deleted(): void
    {
        $kasir = $this->user();
        $started = $this->schedule($kasir, -60, 300);
        $this->openShift($kasir, 100000, $started);
        $past = $this->schedule($kasir, -1500, -1020);
        $future = $this->schedule($kasir, 1440, 1920);

        $this->actingAs($this->user(User::ROLE_ADMIN));
        $this->delete(route('jadwal.destroy', $started))->assertSessionHas('error');
        $this->delete(route('jadwal.destroy', $past))->assertSessionHas('error');
        $this->delete(route('jadwal.destroy', $future))->assertSessionHas('success');

        $this->assertSame(2, $kasir->schedules()->count());
    }

    public function test_schedule_status_shows_attendance(): void
    {
        $kasir = $this->user();
        $absent = $this->schedule($kasir, -1500, -1020);
        $late = $this->schedule($kasir, -60, 300);
        $this->travel(-48)->minutes();
        $this->openShift($kasir, 100000, $late);
        $this->travelBack();

        $this->assertSame('tidak_hadir', $absent->status());
        $this->assertSame('bertugas', $late->fresh()->status());
        $this->assertSame(12, $late->fresh()->lateMinutes());

        $this->actingAs($this->user(User::ROLE_PEMILIK))
            ->get(route('jadwal.index', ['minggu' => $late->waktu_mulai->toDateString()]))
            ->assertOk()
            ->assertSee('Sedang bertugas')
            ->assertSee('Terlambat 12 mnt');
    }
}
