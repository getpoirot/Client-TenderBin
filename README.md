# TenderBinClient
HttpClient(SDK) Of TenderBin Object Store

## Create SDK Instance

```php
$c = new \Poirot\TenderBinClient\Client(
    'http://172.17.0.1:8080/bin'
    , new \Poirot\ApiClient\TokenProviderSolid(
        new \Poirot\ApiClient\AccessTokenObject([
            'access_token' => '#accesstoken',
            'client_id'    => '#clientid',
            'expires_in'   => 3600,
            'scopes'       => 'scope otherscope'
        ])
    )
);

$resource = $c->getBinMeta('58eca65857077400155a1bd2');
```

## Store Bin-Data in Storage

```php
$r = $c->store(
    serialize( ['your_data_any_type'] )
    , 'application/php-serialized'
    , 'TenderBin Client'
    , [
        'some_tag' => 'tag value',
        'is_file'  => true, // force store as a file
    ]
);
```

## Load BinData Content Into Local Stream

```php
list($resource, $meta) = $c->loadBin('58eca65857077400155a1bd2');
header('Content-Type: '. $meta['content_type']); // read header (tags) included with bin
while ($content = fread($resource, 2048)) { // Read in 2048-byte chunks
    echo $content; // or output it somehow else.
    flush(); // force output so far
}
```
