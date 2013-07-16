<section id="batch" title="Batch">
  <div class="page-header">
    <h1>Batch</h1>
  </div>

    <p class="lead">Execute multiple requests at once</p>

    <p>A batch request is always a POST to the <code>batch</code> endpoint. The POST content describes the requests which should be executed in order. The response content will describe the result of each request.</p>

    <p>For example, this batch request will be the same as executing the nine separate requests in order:</p>

<pre class="prettyprint linenums">
POST
HEADERS
Content-type: application/json

CONTENT
{
    "get single author": {
        "uri": "/rest/author/james",
        "method": "GET"
    },
    "get author list": {
        "uri": "/rest/author",
        "method": "GET"
    },
    "get author list of partials": {
        "uri": "/rest/author?select(name)",
        "method": "GET"
    },
    "get filtered author list": {
        "uri": "/rest/author?country=germany",
        "method": "GET"
    },
    "get author list offset": {
        "uri": "/rest/author",
        "method": "GET",
        "headers": {
            "Range": "items=2-100"
        }
    },
    "replace game list": {
        "uri": "/rest/game",
        "method": "POST",
        "content": {"name": "forbidden-island", "type": "co-op"}
    },
    "delete an author": {
        "uri": "/rest/author/harry",
        "method": "DELETE"
    },
    "update a game": {
        "uri": "/rest/game/feed-the-kitty",
        "method": "PUT",
        "content": {"type": "childrens", "author": {"$ref": "author/harry"}}
    },
    "patch a game": {
        "uri": "/rest/game/feed-the-kitty",
        "method": "PATCH",
        "content": {"type": "kids"}
    }
}

http://myserver.com/rest/batch
</pre>

    <p>The response object might look like:</p>

<pre class="prettyprint linenums">
{
    "get single author": {
        "status": 200,
        "content": { ... }
    },
    "get author list": {
        "status": 200,
        "headers": {
            "Content-Range": "x-x/x"
        },
        "content": [ ... ]
    },
    "get author list of partials": {
        "status": 200,
        "headers": {
            "Content-Range": "x-x/x"
        },
        "content": [ ... ]
    },
    "get filtered author list": {
        "status": 200,
        "headers": {
            "Content-Range": "x-x/x"
        },
        "content": [ ... ]
    },
    "get author list offset": {
        "status": 200,
        "headers": {
            "Content-Range": "x-x/x"
        },
        "content": [ ... ]
    },
    "replace game list": {
        "status": 204
    },
    "delete an author": {
        "status": 204
    },
    "update a game": {
        "status": 204
    },
    "patch a game": {
        "status": 204
    }
}
</pre>

</section>
