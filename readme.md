BoltBB
======


topics:
    name: Topics
    singular_name: Topic
    fields:
        title:
            type: text
            class: large
        slug:
            type: slug
            uses: title
        author:
            type: text
        authorip:
            type: text
        forum:
            type: integer
        state:
            type: text
        body:
            type: html
            height: 300px
    record_template: topic.twig
    listing_template: forum.twig
    listing_records: 10
    default_status: published
    sort: datepublish DESC
    recordsperpage: 10

replies:
    name: Replies
    singular_name: Reply
    fields:
        title:
            type: text
            class: large
        slug:
            type: slug
            uses: title
        author:
            type: text
        authorip:
            type: text
        forum:
            type: text
        topic:
            type: text
        body:
            type: html
            height: 300px
    record_template: topic.twig
    listing_template: forum.twig
    listing_records: 10
    default_status: published
    sort: datepublish DESC
    recordsperpage: 10