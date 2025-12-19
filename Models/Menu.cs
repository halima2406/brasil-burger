using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace BrasilBurger.Models   
{
    [Table("menu")] 
    public class Menu
    {
       [Key]
        [Column("id")]
        public int Id { get; set; }

        [Column("nom")]
        public string Nom { get; set; } = "";

        [Column("image")]
        public string? Image { get; set; }

        [Column("burger_id")]
        public int BurgerId { get; set; }

        [Column("boisson_id")]
        public int BoissonId { get; set; }

        [Column("frite_id")]
        public int FriteId { get; set; }

        [Column("est_archive")]
        public bool EstArchive { get; set; } = false;

        public decimal Prix
        {
            get
            {
                decimal total = 0;
                if (Burger != null)
                {
                    total += Burger.Prix;
                }
                if (Boisson != null)
                {
                    total += Boisson.Prix;
                }
                if (Frite != null)
                {
                    total += Frite.Prix;
                }
                return total;
                
            }
        }

         [ForeignKey("BurgerId")]
        public virtual Produit? Burger { get; set; }

        [ForeignKey("BoissonId")]
        public virtual Produit? Boisson { get; set; }

        [ForeignKey("FriteId")]
        public virtual Produit? Frite { get; set; }

        
    }
    
}