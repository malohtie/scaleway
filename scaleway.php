<?php

class scaleway
{
    protected $apiurl = '.scaleway.com';
    protected $account = 'https://account';
    protected $region = ['par1', 'ams1'];
    protected $compute = 'https://cp-';
    protected $apikey;
    protected $defaultDomain;
    protected $data_header;
    protected $used_region;


    public function __construct($apikey, $domain)
    {
        $this->defaultDomain = $domain;
        $this->apikey = $apikey;
        $this->data_header = array (
            "Content-Type: application/json"
        );
    }

    private function callAPI($url, $headerdata, $data = NULL, $method = 'GET')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($method == 'POST' && $data != NULL)
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        elseif($method == 'PUT' && $data != NULL)
        {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        else
        {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerdata);

        $json = json_decode(curl_exec($ch), true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $json['code'] = $httpCode;
        if (curl_errno($ch)) {
            die(json_encode(array("status" => FALSE, "response" => curl_error($ch) )));
        }
        curl_close ($ch);

        return $json;
    }


    //key images
    public function get_images()
    {
        $this->used_region = $this->region[array_rand($this->region)];
        $link = $this->compute.$this->used_region.$this->apiurl.'/images';

        $this->data_header[] = "X-Auth-Token: ".$this->apikey;
        return $this->callAPI($link, $this->data_header);

    }
    //get token
    public function get_tokens()
    {
        $link = $this->account.$this->apiurl.'/tokens';
        $this->data_header[] = "X-Auth-Token: ".$this->apikey;
        return $this->callAPI($link, $this->data_header);
    }

    //get organization
    public function get_organizations()
    {
        $link = $this->account.$this->apiurl.'/organizations';
        $this->data_header[] = "X-Auth-Token: ".$this->apikey;
        return $this->callAPI($link, $this->data_header);
    }

    //create server
    public function create_server($organization, $name, $size = 'START1-S', $ipv6 = true, $bootType = 'local')
    {
        $this->used_region = $this->region[array_rand($this->region)];
        $link = $this->compute.$this->used_region.$this->apiurl.'/servers';

        if($this->used_region == 'par1')
        {
            $data = array(
                "organization" => $organization,
                "name" => $name.'.'.$this->defaultDomain,
                "image" => '37832f54-c18f-4338-a552-113e4302a236',
                "commercial_type" => $size,
                "tags" => [],
                "enable_ipv6" => $ipv6,
                "boot_type" => $bootType
            );
            return $this->callAPI($link, $this->data_header, $data, 'POST');
        }

        if($this->used_region == 'ams1')
        {
            $data = array(
                "organization" => $organization,
                "name" => $name.'.'.$this->defaultDomain,
                "image" => 'e338d2ea-262d-45a1-95d2-300adce63cdd',
                "commercial_type" => $size,
                "tags" => [],
                "enable_ipv6" => $ipv6,
                "boot_type" => $bootType
            );
            return $this->callAPI($link, $this->data_header, $data, 'POST');
        }

    }

    //make action to server poweron, backup, stop_in_place, poweroff
    public function action($action, $idServer, $region = false)
    {
        if(!empty($region))
            $link = $this->compute.$region.$this->apiurl."/servers/{$idServer}/action";
        else
            $link = $this->compute.$this->used_region.$this->apiurl."/servers/{$idServer}/action";

        $this->data_header[] = "X-Auth-Token: ".$this->apikey;
        $data = array(
            "action" => $action

        );

        return $this->callAPI($link, $this->data_header, $data, 'POST');
    }

    //get servers in asm1
    public function get_servers_asm1()
    {
        $link = $this->compute.'ams1'.$this->apiurl.'/servers';
        $this->data_header[] = "X-Auth-Token: ".$this->apikey;
        $data = $this->callAPI($link, $this->data_header);
        if(isset($data['servers']))
        {
            foreach ($data['servers'] as $key => $value) {
                $data['servers'][$key]['region'] = 'ams1';
            }
        }
        return $data;
    }

    //get servers in part1
    public function get_servers_par1()
    {
        $link = $this->compute.'par1'.$this->apiurl.'/servers';
        $this->data_header[] = "X-Auth-Token: ".$this->apikey;
        $data = $this->callAPI($link, $this->data_header);
        if(isset($data['servers']))
        {
            foreach ($data['servers'] as $key => $value) {
                $data['servers'][$key]['region'] = 'par1';
            }
        }
        return $data;
    }

    //delete server
    public function delete_server($idServer, $region)
    {
        $link = $this->compute.$region.$this->apiurl.'/servers/'.$idServer;
        $this->data_header[] = "X-Auth-Token: ".$this->apikey;
        return $this->callAPI($link, $this->data_header, NULL, 'DELETE');

    }

}
