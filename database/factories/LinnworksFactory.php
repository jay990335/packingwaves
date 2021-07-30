<?php

namespace Database\Factories;

use App\Linnworks;
use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LinnworksFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Linnworks::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'token' => '2eafda51-6c7f-583f-0c03-0593ab99bfc2',
            'applicationId' => '9a50e415-9916-4a50-8c57-b13a73b33216',
            'applicationSecret' => 'f99c133b-df5c-4189-ae21-d86d47ac6a7d',
            'passportAccessToken' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiY2Q5NDE4NTY2YzNlZmQyZWE1YWQwMmRlMmU3YjA0NzhhYWQ1MTEyZmM5Y2RmM2JkZGQ0MWZkOGZmOTI5NjExZDJhYTM5YTUxODFhNmQyNTEiLCJpYXQiOjE2MjU4MjU0OTguNjYwOTgsIm5iZiI6MTYyNTgyNTQ5OC42NjA5ODUsImV4cCI6MTY1NzM2MTQ5OC42MzIxNzgsInN1YiI6IjEiLCJzY29wZXMiOltdfQ.guv3hEODAhASqvU9PNZL0tA7FahrGuQQ3xvfZ0sskEjd963-jEuJ3zsJWEiYD0h_lixT1CHw4xhT9oekKRvVgOl0JLr7i9zlUswLNMZxLjoWCAUKxVT7pCQbn--W84ngJoj9DGPqbwvTkRC8QfBFSuxfKvKD2_RZE3wtRDp7GpuCz7sK0saeihZ8Pr4aquFuf-oWK9pHYTyxGSlD8oHQIc-rglikXMrsX_oZxcu7G1oCwvDShgsZe98cqrmOZAF2PY3Bs8zDmbfQE1dxI6yQ_PABt6-j_CBC0yoqAXdCXuMESDOkGAtAA15PAWF6tEyXaiBZkMmYmRIGm07TpM0fBZkLS3Ii2Dyc_aTuZ5QO35ALmge4WlHupuruH8bxkqmKpmoHme_FdwE7ptjYmnIY-ol5ST1x1ySbnDYNEqz-GZ9WHzBdT2bC2koE4N4BCCsBN0kYWXezgrRoZHoc02PF27Tri6pafbWm2HAswLCzbHYX9sjk1XvpdSuAOJLGozxOZdGmWGSwZOH2zWCpD5vRVY6XVc1C4eqNGiX0cdjhYZImoT6f5jgQVhGnspFGcnZR_RS-TM8dfctNM0gGYPBPzc9LGp8-WkNPDdMJ54h3mfZKVzdGDJ5hisMTFSEBEdzwxNG7hiyuKvaBHNBWWSTw0bLTQ2THDzecPs2UMb0Yoz8',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
