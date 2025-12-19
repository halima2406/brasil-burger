using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace BrasilBurger.Models
{
    [Table("ligne_commande")]
    public class LigneCommande
    {
        [Key]
        [Column("id")]
        public int Id { get; set; }

        [Column("commande_id")]
        public int CommandeId { get; set; }

        [Column("produit_id")]
        public int? ProduitId { get; set; }

        [Column("menu_id")]
        public int? MenuId { get; set; }

        [Column("quantite")]
        public int Quantite { get; set; }

        [Column("prix_unitaire")]
        public decimal PrixUnitaire { get; set; }

        [Column("montant_total")]
        public decimal MontantTotal { get; set; }

        [Column("type_ligne")]
        public string TypeLigne { get; set; } = "";

       
        [ForeignKey("CommandeId")]
        public virtual Commande? Commande { get; set; }

        [ForeignKey("ProduitId")]
        public virtual Produit? Produit { get; set; }

        [ForeignKey("MenuId")]
        public virtual Menu? Menu { get; set; }
    }
}