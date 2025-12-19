using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace BrasilBurger.Models
{
    [Table("livreur")]
    public class Livreur
    {
        [Key]
        [Column("id")]
        public int Id { get; set; }

        [Column("nom")]
        public string Nom { get; set; } = "";

        [Column("telephone")]
        public string Telephone { get; set; } = "";

        [Column("est_archive")]
        public bool EstArchive { get; set; } = false;
    }
}