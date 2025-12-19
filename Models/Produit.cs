using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;
using System.Runtime.InteropServices;

namespace BrasilBurger.Models
{
    [Table("produit")]
    public class Produit
    {
        [Key]
        [Column("id")]
        public int Id { get; set; }

        [Column("nom")]
        public string Nom { get; set; } = string.Empty;
        //Ou je peux aussi mettre = "" ; 

        [Column("prix")]
        public decimal Prix { get; set; }

        [Column("image")]
        public string? Image { get; set; }

        [Column("type_produit")]
        public string TypeProduit { get; set; } = "";

        [Column("type_complement")]
        public string? TypeComplement { get; set; }

        [Column("est_archive")]
        public bool EstArchive { get; set; } = false;
    }
}