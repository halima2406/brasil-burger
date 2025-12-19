using Microsoft.EntityFrameworkCore;
using BrasilBurger.Models;

namespace BrasilBurger.Data
{
    public class ApplicationDbContext : DbContext
    {
        public ApplicationDbContext(DbContextOptions<ApplicationDbContext> options)
            : base(options)
        {
        }

        public DbSet<Produit> Produits { get; set; }
        public DbSet<Menu> Menus { get; set; }
        public DbSet<User> Users { get; set; }
        public DbSet<Commande> Commandes { get; set; }
        public DbSet<LigneCommande> LignesCommande { get; set; }
        public DbSet<Zone> Zones { get; set; }
        public DbSet<Livreur> Livreurs { get; set; }
        public DbSet<Paiement> Paiements { get; set; }
    }
}