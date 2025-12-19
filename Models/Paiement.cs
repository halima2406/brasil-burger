using System;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace BrasilBurger.Models
{
    [Table("paiement")]
    public class Paiement
    {
        [Key]
        [Column("id")]
        public int Id { get; set; }

        [Column("commande_id")]
        public int CommandeId { get; set; }

        [Column("montant")]
        public decimal Montant { get; set; }

        [Column("date_paiement")]
        public DateTime DatePaiement { get; set; } = DateTime.UtcNow;

        [Column("type_paiement")]
        public string TypePaiement { get; set; } = "";

       
        [ForeignKey("CommandeId")]
        public virtual Commande? Commande { get; set; }
    }
}