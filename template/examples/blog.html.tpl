[% extends 'base.html.tpl' %]

[% block content %]

    <!-- Section d'abonnement -->
    <section class="bg-quaternary text-light"
             style="padding:var(--spacing-large); border-radius:var(--radius); margin-bottom:var(--spacing-large);">
      <h2 class="text-dark"><span class="icon">mail</span> Subscribe</h2>
      <form>
        <div class="form-group">
          <label for="email"><span class="icon">email</span> Email</label>
          <input type="email" id="email" placeholder="you@example.com" />
        </div>
        <button type="submit" class="btn outline primary">
          <span class="icon">send</span> Subscribe
        </button>
      </form>
    </section>

    <!-- Section des derniers articles -->
    <section>
      <h2 class="text-secondary"><span class="icon">article</span> Latest Posts</h2>
      <div class="row" style="margin-top:var(--spacing);">
        <!-- Post 1 -->
        <article class="col-4">
          <div class="card card-primary">
            <img src="post1.jpg" alt="Mountains" class="filter-primary" style="width:100%; border-radius:var(--radius);"/>
            <h3 class="text-primary"><span class="icon">landscape</span> Exploring the Mountains</h3>
            <small class="text-quaternary">April 15, 2024</small>
            <p>Discover the majestic beauty of the mountain landscape…</p>
            <button class="btn secondary">
              <span class="icon">read_more</span> Read More
            </button>
          </div>
        </article>
        <!-- Post 2 -->
        <article class="col-4">
          <div class="card card-secondary">
            <img src="post2.jpg" alt="Forest Bathing" class="filter-secondary" style="width:100%; border-radius:var(--radius);"/>
            <h3 class="text-secondary"><span class="icon">forest</span> The Art of Forest Bathing</h3>
            <small class="text-quaternary">April 3, 2024</small>
            <p>Learn about the practice of forest bathing and its benefits…</p>
            <button class="btn outline tertiary">
              <span class="icon">read_more</span> Read More
            </button>
          </div>
        </article>
        <!-- Post 3 -->
        <article class="col-4">
          <div class="card card-tertiary">
            <img src="post3.jpg" alt="Eco Travel" class="filter-tertiary" style="width:100%; border-radius:var(--radius);"/>
            <h3 class="text-tertiary"><span class="icon">flight</span> Tips for Sustainable Travel</h3>
            <small class="text-quaternary">March 22, 2024</small>
            <p>Find out how you can reduce your carbon footprint…</p>
            <button class="btn outline quaternary">
              <span class="icon">read_more</span> Read More
            </button>
          </div>
        </article>
      </div>
    </section>

    <!-- Section d'alertes -->
    <section style="margin:var(--spacing-large) 0;">
      <div class="alert alert-info">
        <span class="icon">info</span> This is an info alert.
      </div>
      <div class="alert alert-warning">
        <span class="icon">warning</span> This is a warning alert.
      </div>
      <div class="alert alert-error">
        <span class="icon">error</span> This is an error alert.
      </div>
    </section>
  [% endblock %]