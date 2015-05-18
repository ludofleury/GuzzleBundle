# Guzzle Bundle [![Build Status](https://travis-ci.org/ludofleury/GuzzleBundle.png?branch=master)](https://travis-ci.org/ludofleury/GuzzleBundle) [![Latest Stable Version](https://poser.pugx.org/playbloom/guzzle-bundle/v/stable.png)](https://packagist.org/packages/playbloom/guzzle-bundle) [![Total Downloads](https://poser.pugx.org/playbloom/guzzle-bundle/downloads.png)](https://packagist.org/packages/playbloom/guzzle-bundle)

Provide a basic logger and an advanced profiler for Guzzle

* The basic logger use the default Symfony app logger, it's safe to use in your production environement.
* The advanced profiler is for debug purposes and will display a dedicated report available in the toolbar and Symfony Web Profiler


<img src="http://ludofleury.github.io/GuzzleBundle/images/guzzle-profiler-panel.png" width="280" height="175" alt="Guzzle Symfony web profiler panel"/>
<img src="http://ludofleury.github.io/GuzzleBundle/images/guzzle-request-detail.png" width="280" height="175" alt="Guzzle Symfony web profiler panel - request details"/>
<img src="http://ludofleury.github.io/GuzzleBundle/images/guzzle-response-detail.png" width="280" height="175" alt="Guzzle Symfony web profiler panel - response details"/>

## Installation

Add the composer requirements
```javascript
{
    "require-dev": {
        "playbloom/guzzle-bundle": "v1.1.0"
    },
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

Concrete [Guzzle client creation](http://guzzle.readthedocs.org/en/latest/clients.html#creating-a-client) can be easily managed by the Symfony service container thanks to a [simple factory configuration](http://symfony.com/doc/current/components/dependency_injection/factories.html), in this case, you just need to tag your guzzle service(s) with `playbloom_guzzle.client`.

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

That's it, now your profiler panel displays your custom information and the Guzzle API requests.

## TODO

* Add extra information about the client configuration itself (thanks to the guzzle service builder?)
* Add clients|host|endpoint|time filters for http requests

## Licence

This bundle is under the MIT license. See the complete license in the bundle

## Credits

* Swagger for the UI


[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/ludofleury/guzzlebundle/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

