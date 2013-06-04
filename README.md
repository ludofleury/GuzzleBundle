# Playbloom Guzzle Bundle

Provide a basic logger and an advanced profiler for Guzzle

* The basic logger use the default Symfony app logger, it's safe to use in your production environement.
* The advanced profiler is for debug purposes and will display a dedicated report available in the toolbar and Symfony Web Profiler

<img src="https://www.evernote.com/shard/s282/sh/0d175de7-29b6-4bd6-b0b0-4d14f7447489/ca61946cc7a5eeeb07bfe820c9037019/res/170ac2f9-0dcb-4d39-b73d-7fc6a6ce2cfa/skitch.png" witdh="280" height="175" alt="Guzzle Symfony web profiler panel">
<img src="https://www.evernote.com/shard/s282/sh/5f8d3d3b-8f8f-411b-b41e-4ed399dce6e9/796213f2dfc5d3b489f95a9c5646103f/res/d55806d9-8055-40b2-8fd7-d1e1bf1b456d/skitch.png" witdh="280" height="175" alt="Guzzle Symfony web profiler panel - request details">
<img src="https://www.evernote.com/shard/s282/sh/a5f1dc50-e4f7-4048-a84e-97f9c2190f30/1327fee5e07958e14d412351cc4917ae/res/9a25fceb-f6f3-4d52-aafb-6ef4a52ffac9/skitch.png" witdh="280" height="175" alt="Guzzle Symfony web profiler panel - response details">

## Installation

Add the composer requirements (will be soon on packagist)
```javascript
{
    "require-dev": {
        "playbloom/guzzle-bundle": "dev-master"
    },

    "repositories": [
        {
            "type": "git",
            "url": "git://github.com/ludofleury/GuzzleBundle.git"
        }
    ]
}
```

Add the bundle to your Symfony app kernel
```php
<?php
    // in %your_project%/app/AppKernel.php
    $bundles[] = new Playbloom\Bundle\GuzzleBundle\PlaybloomGuzzleBundle();
?>
```

To enable the advanced profiler & the toolbar/web profiler panel, add this line to your `app/config/config_dev.yml`
```yml
playbloom_guzzle:
    web_profiler: true
```

### Guzzle client as a Symfony service

Concrete [Guzzle client creation](http://guzzlephp.org/tour/building_services.html#create-a-client) can be easily managed by the Symfony service container thanks to a [simple factory configuration](http://symfony.com/doc/current/components/dependency_injection/factories.html), in this case, you just need to tag your guzzle service(s) with `playbloom_guzzle.client`.

It will add the basic logger to your client(s). If the web_profiler is enabled in the current environement, it will also add the advanced profiler and display report on the Symfony toolbar/web profiler.

```xml
<service id="acme.client"
    class="%acme.client.class%"
    factory-class="%acme.client.class%"
    factory-method="factory">
    <!-- your arguments -->
    <tag name="playbloom_guzzle.client" />
</service>
```

### Add the logger/profiler manually to a Guzzle client

If you need to handle the registration of the logger or profiler plugin manually, you can retrieve theses services from the Symfony container.

```php
<?php

$client = new \Guzzle\Http\Client('https://my.api.com');

// basic logger service plugged & configured with the default Symfony app logger
$loggerPlugin = $container->get('playbloom_guzzle.client.plugin.logger');
$client->addSubscriber($loggerPlugin);

// advanced profiler for developement and debug, requires web_profiler to be enabled
$profilerPlugin = $container->get('playbloom_guzzle.client.plugin.profiler');
$client->addSubscriber($profilerPlugin);

?>
```

## Customize your own profiler panel

If you need a [custom profiler panel](http://symfony.com/doc/master/cookbook/profiler/data_collector.html) you can extend/reuse easily the data collector and profiler template from this bundle.

For example, you have a GithubBundle which interact with the Github API. You also have a Github profiler panel to debug your developement and you want to have the API requests profiled in this panel.

It's quite easy:
First, define your own `GithubDataCollector` extending the `Playbloom\Bundle\GuzzleBundle\DataCollector\GuzzleDataCollector`


Then extends the guzzle web profiler template
```twig
{% extends 'PlaybloomGuzzleBundle:Collector:guzzle.html.twig' %}

{% block panel %}
    <div class="github">
        <h2>Github</h2>
        <ul>
            <li><strong>Github API key:</strong> {{ collector.getApiKey }}</li>
            <!-- Some custom information -->
        </ul>
    </div>

    {% include 'PlaybloomGuzzleBundle:Profiler:requests.html.twig' with {'requests': collector.requests } %}
{% endblock %}
```

And finally declare your data collector
```xml
<service id="data_collector.github" class="Acme\GithubBundle\DataCollector\GithubDataCollector">
    <argument type="service" id="playbloom_guzzle.client.plugin.profiler"/>
    <tag name="data_collector"
        template="AcmeGithubBundle:Collector:github"
        id="github"/>
</service>
```

That's it, now your profiler panel display your custom informations and the Guzzle API requests.

## TODO

* Add extra information about the client configuration itself (thanks to the guzzle service builder?)
* Add clients|host|endpoint|time filters for http requests

## Licence

This bundle is under the MIT license. See the complete license in the bundle:

    Resources/meta/LICENSE

## Credits

* Swagger for the UI
