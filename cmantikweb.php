<php

require __DIR__.'/vendor/autoload.php';

set_time_limit(0);
date_default_timezone_set('UTC');

class BOTCMKW
{
    public function __construct()
    {
        $this->config = [
          'userName'      => '',
          'password'      => '',
          'debug'         => false,
          'truncateDebug' => false,
          'rankToken'     => ''
        ];

        $this->nFollowers = 0;
        $this->nFollowed  = 0;

        $this->ig = new \InstagramAPI\Instagram($this->config['debug'], $this->config['truncateDebug']);

        try {
            $this->ig->login($this->config['userName'], $this->config['password']);
        } catch (\Exception $e) {
            echo 'Something went wrong: '.$e->getMessage()."\n";
            exit(0);
        }

        $this->config['rankToken'] = \InstagramAPI\Signatures::generateUUID();
    }

    private function getFollowers()
    {
        $totalFollowers = 0;
        $maxId          = null;
        $followers      = [];
        echo " Comenzando a obtener lista de seguidores: .\n";
        do {
            echo " En progreso... .\n";
            $response        = $this->ig->people->getSelfFollowers($this->config['rankToken'], null, $maxId);
            $tmpFollowers    = $response->getUsers();
            $followers       = array_merge($followers, $tmpFollowers);
            $maxId           = $response->getNextMaxId();
            $totalFollowers += count($tmpFollowers);
            $segSleep        = rand(6, 10);
            echo "Durmiendo la peticion por {$segSleep}s...\n";
            sleep($segSleep);
        } while ($maxId !== null);

        $this->nFollowers = $totalFollowers;
        return $followers;
    }

    private function getFollowed()
    {
        $response = $this->ig->people->getSelfFollowing($this->config['rankToken']);
        $followed = $response->getUsers();
        $this->nFollowed  = count($followed);

        return $followed;
    }

    public function balanceFollower()
    {
        try {
            $followers = $this->getFollowers();
            $followed  = $this->getFollowed();

            $statistics = [
              'nFollowers'         => ($nfollowers = $this->nFollowers),
              'nFollowed'          => ($nfollowed  = $this->nFollowed),
              'porcentageImbalace' => round((($nfollowed - $nfollowers) / $nfollowed) * 100)
            ];

            $listPkFollowers = [];
            $listPkFollowed = [];

            echo " # | Seguidores: {$statistics['nFollowers']} \n";
            echo " # | Seguidos {$statistics['nFollowed']} \n";
            echo " % | Porcentaje de Desbalance {$statistics['porcentageImbalace']}%\n \n";

            echo " Preparando la carga de Ids \n \n";

            foreach ($followers as $key => $user) {
                array_push($listPkFollowers, $user->getPk());
            }

            foreach ($followed as $key => $user) {
                array_push($listPkFollowed, $user->getPk());
            }

            //Usuarios que no te siguen.
            $DiffUsers = array_diff($listPkFollowed, $listPkFollowers);

            echo " Limpiando los seguidores... \n";
            foreach ($DiffUsers as $key => $id) {
                $this->ig->people->unfollow($id);
                echo " Seguidor Eliminado  \n";
                sleep(rand(6, 20));
            }
            echo " Fin \n";
        } catch (\Exception $e) {
            echo 'Something went wrong: '.$e->getMessage()."\n";
        }
    }

    public function obtainFollow()
    {
        echo "Escribe el nombre de usuario target: ";
        $userName = trim(fgets(STDIN));

        //Buscamos el id del usuario segun su nombre.
        $userId = $this->ig->people->getUserIdForName($userName);
        echo $userId ."\n";

        $maxId          = null;
        $followers      = [];
        $totalFollowers = 0;
        do {
            echo " En progreso... .\n";

            $response           = $this->ig->people->getFollowers($userId, $this->config['rankToken'], null, $maxId);
            $tmpFollowers       = $response->getUsers();
            $followers          = array_merge($followers, $tmpFollowers);
            $maxId              = $response->getNextMaxId();
            $totalFollowers    += count($tmpFollowers);
            $segSleep           = rand(6, 10);

            echo " Durmiendo la peticion por {$segSleep}\n";
            sleep($segSleep);
        } while ($maxId !== null);

        echo " Numero de seguidores del target {$totalFollowers}...\n";
        $count = 0;

        foreach ($followers as $key => $user) {
            $count +=1;
            echo "Id: " . $user->getPk() . " -- ". "Username Acount: " . $user->getUsername() ."\n";

            $this->ig->people->follow($user->getPk());
            //Hay que dormir la consulta 6 Segundos para que instagram no nos corte la comunicacion
            sleep(rand(10, 30));
            echo "# Finish Follow User For User" ."\n";
        }
        echo "# Followers: " . $count . "\n";
    }
}
