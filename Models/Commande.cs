using System;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace BrasilBurger.Models
{
    [Table("commande")]
    public class Commande
    {
        [Key]
        [Column("id")]
        public int Id { get; set; }

        [Column("client_id")]
        public int ClientId { get; set; }

        [Column("date_commande")]
        public DateTime DateCommande { get; set; } = DateTime.UtcNow;

        [Column("statut")]
        public string Statut { get; set; } = "EN_COURS";

        [Column("type_consommation")]
        public string TypeConsommation { get; set; } = "";

        [Column("montant_total")]
        public decimal MontantTotal { get; set; }

        [Column("livreur_id")]
        public int? LivreurId { get; set; }

        [Column("zone_id")]
        public int? ZoneId { get; set; }

    
        [ForeignKey("ClientId")]
        public virtual User? Client { get; set; }

        [ForeignKey("LivreurId")]
        public virtual Livreur? Livreur { get; set; }

        [ForeignKey("ZoneId")]
        public virtual Zone? Zone { get; set; }

       public virtual ICollection<LigneCommande> LignesCommande { get; set; } = new List<LigneCommande>();
        public virtual Paiement? Paiement { get; set; }
    }
}