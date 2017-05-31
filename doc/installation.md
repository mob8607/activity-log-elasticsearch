# Installation


Add bundle to AbstractKernel:

```
new ONGR\ElasticsearchBundle\ONGRElasticsearchBundle(),
new Sulu\Bundle\ElasticsearchActivityLogBundle\ElasticsearchActivityLogBundle(),
```

Add to app/config/config.yml:

```
ongr_elasticsearch:
    managers:
        default:
            index:
                index_name: %index_name%
            mappings:
                - ElasticsearchActivityLogBundle
```
