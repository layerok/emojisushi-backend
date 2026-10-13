<?php namespace Layerok\PosterPos\Models;

use Illuminate\Support\Facades\Hash;
use Model;
use poster\src\PosterApi;

/**
 * A courier, mapped to the Poster employee whose id appears on deliveries.
 */
class Courier extends Model
{
    public $table = 'layerok_posterpos_couriers';

    use \October\Rain\Database\Traits\Validation;

    protected $fillable = [
        'name',
        'phone',
        'login',
        'password',
        'poster_user_id',
        'rate_per_km',
        'active',
    ];

    // The hash never leaves the model, not even in the courier app's API.
    protected $hidden = ['password'];

    public $rules = [
        'name' => 'required',
        // One Poster employee maps to one courier, otherwise a delivery's
        // courier_id would resolve to two different people.
        'poster_user_id' => 'nullable|unique:layerok_posterpos_couriers,poster_user_id',
        // The login is how the courier app finds the courier.
        'login' => 'nullable|unique:layerok_posterpos_couriers,login',
    ];

    public $customMessages = [
        'poster_user_id.unique' => 'Этот сотрудник Poster уже назначен другому курьеру.',
        'login.unique' => 'Этот логин уже занят другим курьером.',
    ];

    /**
     * The unique rules have to ignore the record being edited, otherwise saving
     * a courier without changing anything would collide with itself.
     */
    public function beforeValidate()
    {
        if ($this->exists) {
            $this->rules['poster_user_id'] =
                'nullable|unique:layerok_posterpos_couriers,poster_user_id,' . $this->id;
            $this->rules['login'] =
                'nullable|unique:layerok_posterpos_couriers,login,' . $this->id;
        }
    }

    /**
     * The password input is always rendered empty, so an empty value on save
     * means "unchanged" rather than "clear it". Anything else typed there is
     * stored as a hash.
     */
    public function beforeSave()
    {
        if (!$this->isDirty('password')) {
            return;
        }

        if (trim((string) $this->password) === '') {
            $this->password = $this->exists ? $this->getOriginal('password') : null;

            return;
        }

        if (Hash::info($this->password)['algoName'] === 'unknown') {
            $this->password = Hash::make($this->password);
        }
    }

    /**
     * A new password logs the courier out everywhere, and a removed courier
     * leaves no working sessions behind.
     */
    public function afterSave()
    {
        if ($this->wasChanged('password')) {
            CourierToken::where('courier_id', $this->id)->delete();
        }
    }

    public function afterDelete()
    {
        CourierToken::where('courier_id', $this->id)->delete();
    }

    public function checkPassword(string $password): bool
    {
        return $this->password !== null && $this->password !== '' && Hash::check($password, $this->password);
    }

    public $hasMany = [
        'tasks' => CourierTask::class,
        'shifts' => CourierShift::class,
        'tokens' => CourierToken::class,
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Poster employees, for the mapping dropdown. Poster has no courier flag, so
     * every employee is offered and the choice is made by whoever knows the team.
     */
    public function getPosterUserIdOptions(): array
    {
        try {
            PosterApi::init(config('poster'));
            $result = (object) PosterApi::access()->getEmployees([]);

            if (isset($result->error)) {
                return [];
            }

            return collect($result->response ?? [])
                ->mapWithKeys(function ($employee) {
                    $employee = (object) $employee;
                    $name = trim((string) ($employee->name ?? '')) ?: ('ID ' . $employee->user_id);

                    return [(int) $employee->user_id => $name . ' (#' . $employee->user_id . ')'];
                })
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
