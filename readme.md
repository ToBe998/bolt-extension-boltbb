BoltBB
======

```
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
            readonly: true
        authorip:
            type: text
            label: "IP address"
            readonly: true
        forum:
            type: integer
            readonly: true
        state:
            type: text
        body:
            type: html
            height: 300px
        subscribers:
            type: textarea
            readonly: true
            hidden: true
    default_status: published

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
            readonly: true
        authorip:
            type: text
            label: "IP address"
            readonly: true
        forum:
            type: text
            readonly: true
        topic:
            type: text
            readonly: true
        body:
            type: html
            height: 300px
    default_status: published
```